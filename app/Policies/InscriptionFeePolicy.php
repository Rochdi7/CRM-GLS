<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InscriptionFee;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;

/**
 * Fee lines are managed from the parent inscription — one dedicated
 * permission (`registrations.manage-fees`), center-scoped through the
 * parent inscription.
 */
final class InscriptionFeePolicy
{
    public function create(User $user): bool
    {
        return $user->can('registrations.manage-fees');
    }

    public function update(User $user, InscriptionFee $fee): bool
    {
        return $user->can('registrations.manage-fees') && $this->withinCenter($user, $fee);
    }

    public function delete(User $user, InscriptionFee $fee): bool
    {
        return $user->can('registrations.manage-fees') && $this->withinCenter($user, $fee);
    }

    private function withinCenter(User $user, InscriptionFee $fee): bool
    {
        $centerId = $fee->inscription?->etablissement_id;

        return app(CenterAccessService::class)
            ->canAccessCenter($user, $centerId === null ? null : (int) $centerId);
    }
}
