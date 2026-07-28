<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CaisseTransfer;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class CaisseTransferPolicy extends ResourcePolicy
{
    protected string $module = 'cash-transfers';

    // A transfer reaches its center through the SOURCE till.
    protected function centerId(Model $model): ?int
    {
        $id = $model->caisseSource?->etablissement_id;

        return $id === null ? null : (int) $id;
    }

    /**
     * Approval step — moves real money. The "requester ≠ validator" rule is
     * enforced in Domain\Finance\Actions\ValiderTransfertCaisse (two-person
     * fraud control); this policy handles the permission + center gate.
     */
    public function validate(User $user, CaisseTransfer $transfer): bool
    {
        return $user->can('cash-transfers.validate') && $this->withinCenter($user, $transfer);
    }
}
