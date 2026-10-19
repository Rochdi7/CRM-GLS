<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Inscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * L'étudiant est de retour : l'inscription archivée redevient « Active » et
 * réapparaît dans la liste courante. Motif, date de retour et auteur de
 * l'archivage sont EFFACÉS du dossier — ils restent lisibles dans le
 * journal d'audit (`inscription_archived` / `inscription_reactivated`), la
 * seule mémoire des pauses successives.
 *
 * Même garde que la réactivation d'un dossier annulé (DB-08) : un étudiant
 * n'a jamais deux inscriptions Actives dans la même année — si un autre
 * dossier a été ouvert pendant la pause, c'est lui qui vit, et celui-ci se
 * réveille seulement une fois l'autre clos.
 */
final class ReactiverInscription
{
    public function handle(Inscription $inscription, ?User $par): void
    {
        DB::transaction(function () use ($inscription, $par): void {
            $locked = Inscription::query()->whereKey($inscription->id)->lockForUpdate()->firstOrFail();

            if ($locked->statut !== Inscription::STATUT_ARCHIVEE) {
                throw ValidationException::withMessages([
                    'archive' => __('This registration is not archived.'),
                ]);
            }

            $hasActive = Inscription::query()
                ->where('student_id', $locked->student_id)
                ->where('annee_scolaire_id', $locked->annee_scolaire_id)
                ->where('id', '!=', $locked->id)
                ->where('statut', Inscription::STATUT_ACTIVE)
                ->exists();

            if ($hasActive) {
                throw ValidationException::withMessages([
                    'archive' => __('This student already has an active registration for this academic year.'),
                ]);
            }

            $archiveLe = $locked->archive_le;
            $dateRetour = $locked->archive_date_retour;
            $motif = $locked->archive_motif;

            $locked->statut = Inscription::STATUT_ACTIVE;
            $locked->archive_motif = null;
            $locked->archive_date_retour = null;
            $locked->archive_le = null;
            $locked->archive_par_user_id = null;
            $locked->save();

            activity('inscription')
                ->performedOn($locked)
                ->causedBy($par)
                ->event('inscription_reactivated')
                ->withProperties([
                    'reference' => $locked->reference,
                    'archive_le' => $archiveLe?->toDateTimeString(),
                    'date_retour_prevue' => $dateRetour?->toDateString(),
                    'motif_archivage' => $motif,
                ])
                ->log(sprintf('Inscription %s réactivée', $locked->reference));

            $inscription->setRawAttributes($locked->getAttributes(), true);
        });
    }
}
