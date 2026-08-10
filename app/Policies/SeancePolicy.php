<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Seance;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;

final class SeancePolicy extends ResourcePolicy
{
    protected string $module = 'attendance';

    /**
     * Saving/updating the roll call of a séance (fiche de présence).
     */
    public function mark(User $user, Seance $seance): bool
    {
        return $user->can('attendance.mark') && $this->withinCenter($user, $seance);
    }
}
