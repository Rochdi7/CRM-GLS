<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Inscription;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;

final class InscriptionPolicy extends ResourcePolicy
{
    protected string $module = 'registrations';

    /**
     * "Changement de groupe" both updates the old inscription and creates a
     * new one — requires the dedicated permission plus center scope on the
     * inscription being replaced (mirrors update()'s own gate).
     */
    public function changeGroup(User $user, Inscription $inscription): bool
    {
        return $user->can('registrations.change-group') && $this->withinCenter($user, $inscription);
    }
}
