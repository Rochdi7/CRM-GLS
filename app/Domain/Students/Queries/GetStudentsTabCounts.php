<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Models\User;

/**
 * Badges de la barre d'onglets Étudiants (href ⇒ nombre), servis par les
 * QUATRE pages qui la dessinent (Étudiants, Transferts, Livres, Fusion) —
 * une seule définition, sinon le badge d'un onglet dépendrait de la page
 * d'où on le regarde.
 *
 *  - « Transferts d'étudiants » : demandes en attente (GetStudentTransfersList) ;
 *  - « Étudiants archivés » (19/10/2026) : fiches « Archivé » dans la portée
 *    de l'utilisateur et le centre actif — le même compte que l'onglet
 *    liste et que la carte du tableau de bord (GetStudentsList::archivesCount).
 *
 * @return array<string, int>
 */
final class GetStudentsTabCounts
{
    public function __construct(
        private readonly GetStudentTransfersList $transfers,
        private readonly GetStudentsList $students,
    ) {}

    public function __invoke(User $user): array
    {
        $counts = $this->transfers->tabCounts($user);

        if ($user->can('students.view')) {
            $archives = $this->students->archivesCount($user);
            if ($archives > 0) {
                $counts['/backoffice/students/archives'] = $archives;
            }
        }

        return $counts;
    }
}
