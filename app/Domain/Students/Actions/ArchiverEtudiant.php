<?php

declare(strict_types=1);

namespace App\Domain\Students\Actions;

use App\Models\Student;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Archiver un étudiant (19/10/2026) : il interrompt ses cours mais compte
 * revenir. La fiche passe « Archivé » avec un MOTIF et une DATE DE RETOUR
 * prévue, sort de la liste courante (onglet « Étudiants archivés ») et se
 * réactive d'un clic (ReactiverEtudiant).
 *
 * Ce geste ne touche QUE la fiche : aucune inscription n'est annulée,
 * aucun frais masqué, aucun argent déplacé — l'étudiant revient, et son
 * dossier doit être exactement comme il l'a laissé. Clore un dossier reste
 * une décision à part (« Annuler l'inscription », avec son motif).
 *
 * Refusé sur une fiche « Transféré » (close ici, elle vit sur sa copie) et
 * sur une fiche déjà archivée — un second archivage réécrirait motif et
 * date sans trace de ce qu'on a décidé la première fois.
 */
final class ArchiverEtudiant
{
    public function handle(Student $student, string $motif, CarbonInterface $dateRetour, ?User $par): void
    {
        DB::transaction(function () use ($student, $motif, $dateRetour, $par): void {
            $locked = Student::query()->whereKey($student->id)->lockForUpdate()->firstOrFail();

            if ($locked->estTransfere()) {
                throw ValidationException::withMessages([
                    'archive' => __('A transferred student cannot be archived here - their record now lives in the destination centre.'),
                ]);
            }

            if ($locked->estArchive()) {
                throw ValidationException::withMessages([
                    'archive' => __('This student is already archived.'),
                ]);
            }

            $locked->statut = Student::STATUT_ARCHIVE;
            $locked->archive_motif = $motif;
            $locked->archive_date_retour = $dateRetour->toDateString();
            $locked->archive_le = now();
            $locked->archive_par_user_id = $par?->id;
            $locked->save();

            activity('student')
                ->performedOn($locked)
                ->causedBy($par)
                ->event('student_archived')
                ->withProperties([
                    'reference' => $locked->reference,
                    'nom_complet' => $locked->nomComplet(),
                    'motif' => $motif,
                    'date_retour' => $dateRetour->toDateString(),
                ])
                ->log(sprintf(
                    'Étudiant %s %s archivé — retour prévu le %s : %s',
                    $locked->reference,
                    $locked->nomComplet(),
                    $dateRetour->format('d/m/Y'),
                    $motif,
                ));

            $student->setRawAttributes($locked->getAttributes(), true);
        });
    }
}
