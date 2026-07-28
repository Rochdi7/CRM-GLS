<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class GroupPolicy extends ResourcePolicy
{
    protected string $module = 'groups';

    /**
     * Groups are NEVER deleted (schema §6) — no permission grants this.
     */
    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    /**
     * Transition to "Fin de formation" (Group::archiverCommeTermine).
     */
    public function archive(User $user, Group $group): bool
    {
        return $user->can('groups.archive') && $this->withinCenter($user, $group);
    }
}
