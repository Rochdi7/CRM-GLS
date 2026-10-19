<?php

declare(strict_types=1);

namespace App\Domain\Students\Actions;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * L'étudiant archivé est de retour : la fiche redevient « Actif » et
 * réapparaît dans la liste courante. Motif, date de retour prévue et auteur
 * de l'archivage sont EFFACÉS de la fiche — ils restent lisibles dans le
 * journal d'audit (entrées `student_archived` / `student_reactivated`),
 * qui est la seule mémoire des archivages successifs.
 */
final class ReactiverEtudiant
{
    public function handle(Student $student, ?User $par): void
    {
        DB::transaction(function () use ($student, $par): void {
            $locked = Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

            if (! $locked->estArchive()) {
                throw ValidationException::withMessages([
                    'archive' => __('This student is not archived.'),
                ]);
            }

            $archiveLe = $locked->archive_le;
            $dateRetour = $locked->archive_date_retour;
            $motif = $locked->archive_motif;

            $locked->statut = Student::STATUT_ACTIF;
            $locked->archive_motif = null;
            $locked->archive_date_retour = null;
            $locked->archive_le = null;
            $locked->archive_par_user_id = null;
            $locked->save();

            activity('student')
                ->performedOn($locked)
                ->causedBy($par)
                ->event('student_reactivated')
                ->withProperties([
                    'reference' => $locked->reference,
                    'nom_complet' => $locked->nomComplet(),
                    'archive_le' => $archiveLe?->toDateTimeString(),
                    'date_retour_prevue' => $dateRetour?->toDateString(),
                    'motif_archivage' => $motif,
                ])
                ->log(sprintf('Étudiant %s %s réactivé', $locked->reference, $locked->nomComplet()));

            $student->setRawAttributes($locked->getAttributes(), true);
        });
    }
}
