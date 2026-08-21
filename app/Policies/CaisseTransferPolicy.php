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
     * Approval step — moves real money.
     *
     * RECIPIENT-ONLY: the acting user must own the DESTINATION till. Holding
     * `cash-transfers.validate` is not enough on its own — otherwise any
     * validator could push money between two colleagues' tills without either
     * of them agreeing. The "requester ≠ validator" rule and the same
     * ownership check are re-enforced in
     * Domain\Finance\Actions\ValiderTransfertCaisse (defense in depth: the
     * Domain action is authoritative even if called outside HTTP).
     *
     * ⚠ Deliberately NOT open to super-admins via Gate::before — see
     * AuthServiceProvider's before() hook, which excludes this ability. A
     * super-admin approving a transfer into someone else's till would defeat
     * the whole control.
     */
    public function validate(User $user, CaisseTransfer $transfer): bool
    {
        if (! $user->can('cash-transfers.validate') || ! $this->withinCenter($user, $transfer)) {
            return false;
        }

        $employee = $user->employee;

        if ($employee === null || $employee->id === $transfer->requested_by) {
            return false;
        }

        return $employee->caisses()
            ->whereKey($transfer->caisse_destination_id)
            ->exists();
    }
}
