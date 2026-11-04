<?php

declare(strict_types=1);

namespace App\Domain\Students\Support;

use App\Models\Student;
use Illuminate\Validation\ValidationException;

/**
 * Une fiche « Transféré » (25/09/2026) ne reçoit plus d'AVANCE ni de
 * seconde demande de transfert — l'argent non affecté finirait séparé de la
 * personne, partie sur sa copie (`Student::transfereVers`). Elle reste en
 * revanche INSCRIPTIBLE dans son centre (décision du propriétaire,
 * 04/11/2026) : une nouvelle inscription n'appelle donc plus cette garde.
 *
 * UNE définition, appelée par chaque écriture concernée (avance, demande de
 * transfert) — jamais recopiée dans un read-model.
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
