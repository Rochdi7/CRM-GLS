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

    /**
     * Archiver / réactiver un dossier (19/10/2026) — `registrations.archive`,
     * portée de centre habituelle. Un geste à part de `registrations.update` :
     * il change ce que la liste MONTRE et ce que le recouvrement réclame.
     */
    public function archive(User $user, Inscription $inscription): bool
    {
        return $user->can('registrations.archive') && $this->withinCenter($user, $inscription);
    }
}
