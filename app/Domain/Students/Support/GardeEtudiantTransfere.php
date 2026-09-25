<?php

declare(strict_types=1);

namespace App\Domain\Students\Support;

use App\Models\Student;
use Illuminate\Validation\ValidationException;

/**
 * Une fiche « Transféré » est CLOSE dans son centre (25/09/2026) : plus
 * aucun dossier ni aucun argent ne s'y rattache — tout se passe sur la copie
 * créée dans le centre d'arrivée (`Student::transfereVers`). Sans cette
 * garde, le centre de départ pourrait rouvrir une inscription ou encaisser
 * une avance sur une fiche dont l'étudiant est parti, et l'argent finirait
 * séparé de la personne.
 *
 * UNE définition, appelée par chaque écriture qui prend un `student_id`
 * client (inscription, avance) — jamais recopiée dans un read-model.
 */
final class GardeEtudiantTransfere
{
    public static function assertNonTransfere(Student $student, string $field = 'student_id'): void
    {
        if (! $student->estTransfere()) {
            return;
        }

        $student->loadMissing('transfereVers.etablissement');

        throw ValidationException::withMessages([
            $field => __('This student was transferred to :centre - use their new record there (:reference).', [
                'centre' => $student->transfereVers?->etablissement?->nom_centre ?? '—',
                'reference' => $student->transfereVers?->reference ?? '—',
            ]),
        ]);
    }
}
