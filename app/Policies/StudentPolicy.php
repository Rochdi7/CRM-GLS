<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;

final class StudentPolicy extends ResourcePolicy
{
    protected string $module = 'students';

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
}
