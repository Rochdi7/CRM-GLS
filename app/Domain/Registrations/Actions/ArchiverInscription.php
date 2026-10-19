<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Inscription;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Archiver une inscription (19/10/2026) : l'étudiant interrompt ce cours
 * mais compte revenir (un mois, un trimestre). Le dossier passe « Archivée »
 * avec un MOTIF et une DATE DE RETOUR prévue, sort de la liste courante vers
 * l'onglet « Inscriptions archivées » et se réactive d'un clic
 * (ReactiverInscription).
 *
 * Une PAUSE, pas une clôture : contrairement à « Annuler l'inscription »,
 * aucun frais n'est masqué, aucun paiement libéré en avance, aucun argent
 * déplacé — l'étudiant revient, et son dossier doit être exactement comme il
 * l'a laissé. Le recouvrement ne poursuit que les dossiers `Active`, donc
 * les échéances d'un dossier archivé ne sont pas réclamées pendant la pause.
 *
 * Seule une inscription `Active` s'archive : un dossier clos (Annulée,
 * Changement, Transférée) n'a rien à mettre en pause, et une inscription
 * déjà archivée ne se ré-archive pas — motif et date ne se réécrivent pas
 * sans trace.
 */
final class ArchiverInscription
{
    public function handle(Inscription $inscription, string $motif, CarbonInterface $dateRetour, ?User $par): void
    {
        DB::transaction(function () use ($inscription, $motif, $dateRetour, $par): void {
            $locked = Inscription::query()->whereKey($inscription->id)->lockForUpdate()->firstOrFail();

            if ($locked->statut !== Inscription::STATUT_ACTIVE) {
                throw ValidationException::withMessages([
                    'archive' => __('Only an active registration can be archived.'),
                ]);
            }

            $locked->statut = Inscription::STATUT_ARCHIVEE;
            $locked->archive_motif = $motif;
            $locked->archive_date_retour = $dateRetour->toDateString();
            $locked->archive_le = now();
            $locked->archive_par_user_id = $par?->id;
            $locked->save();

            $locked->loadMissing('student:id,reference,nom,prenom');

            activity('inscription')
                ->performedOn($locked)
                ->causedBy($par)
                ->event('inscription_archived')
                ->withProperties([
                    'reference' => $locked->reference,
                    'etudiant' => $locked->student?->nomComplet(),
                    'motif' => $motif,
                    'date_retour' => $dateRetour->toDateString(),
                ])
                ->log(sprintf(
                    'Inscription %s archivée — retour prévu le %s : %s',
                    $locked->reference,
                    $dateRetour->format('d/m/Y'),
                    $motif,
                ));

            $inscription->setRawAttributes($locked->getAttributes(), true);
        });
    }
}
