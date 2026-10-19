<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Groups\Support\PorteeEnseignant;
use App\Models\Student;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;

final class StudentPolicy extends ResourcePolicy
{
    protected string $module = 'students';

    /**
     * A teacher (`groups.view-own`) lists the students of HIS groups
     * (GetStudentsList applies PorteeEnseignant). The student DETAIL page
     * stays `students.view` only: it shows payments, and a teacher sees no
     * money.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('students.view') || $user->can(PorteeEnseignant::PERMISSION);
    }

    /**
     * Fusion de deux fiches en double (super-admin — `students.merge` est
     * dans PermissionRegistry::superAdminOnly(), donc en pratique seul
     * Gate::before répond oui).
     *
     * ⚠ Volontairement SANS contrôle de centre : un doublon naît le plus
     * souvent d'une ressaisie dans un AUTRE centre, et c'est justement cette
     * paire-là qu'il faut rapprocher. L'écran est réservé au super-admin, qui
     * atteint tout le réseau de toute façon (CLAUDE.md §16).
     */
    public function merge(User $user): bool
    {
        return $user->can('students.merge');
    }

    /**
     * Archiver / réactiver une fiche (19/10/2026) — `students.archive`, avec
     * la portée de centre habituelle. Un geste à part de `students.update` :
     * il change ce que la liste MONTRE (et le compteur du tableau de bord),
     * pas l'identité de la personne.
     */
    public function archive(User $user, Student $student): bool
    {
        return $user->can('students.archive') && $this->withinCenter($user, $student);
    }
}
