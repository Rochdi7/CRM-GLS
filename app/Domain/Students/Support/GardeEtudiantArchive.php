<?php

declare(strict_types=1);

namespace App\Domain\Students\Support;

use App\Models\Student;
use Illuminate\Validation\ValidationException;

/**
 * Une fiche « Archivé » est en pause (19/10/2026) : l'étudiant a
 * interrompu ses cours et doit être RÉACTIVÉ à son retour avant qu'on lui
 * ouvre un nouveau dossier. Sans cette garde, une inscription saisie
 * directement laisserait la fiche archivée alors que la personne est de
 * nouveau en classe — l'onglet « Étudiants archivés » mentirait et le
 * compteur du tableau de bord avec lui.
 *
 * Le refus NOMME la marche à suivre (§11 « signaler plutôt que masquer »).
 * Même forme que GardeEtudiantTransfere : UNE définition, appelée par
 * chaque écriture qui prend un `student_id` client.
 */
final class GardeEtudiantArchive
{
    public static function assertNonArchive(Student $student, string $field = 'student_id'): void
    {
        if (! $student->estArchive()) {
            return;
        }

        throw ValidationException::withMessages([
            $field => __('This student is archived (expected back on :date). Reactivate them from the « Archived students » tab first.', [
                'date' => $student->archive_date_retour?->format('d/m/Y') ?? '—',
            ]),
        ]);
    }
}
