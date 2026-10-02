<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Chèques in hand — same permission + center-scope combination as every
 * other resource. centerId() reads etablissement_id straight off the row
 * (the base default), set from CurrentContext at creation.
 */
final class ChequePolicy extends ResourcePolicy
{
    protected string $module = 'cheques';

    /**
     * Bank lifecycle moves (En possession -> Depose -> Encaisse | Rejete) and
     * the « restitue » bookkeeping. Deliberately a SEPARATE ability from
     * update(): carrying the cheques to the bank is everyone's job, while
     * rewriting a cheque's identity stays with the management roles
     * (07/09/2026). Center scope is unchanged - a user still only touches
     * cheques of the centres they are assigned to.
     */
    public function deposit(User $user, Model $model): bool
    {
        return $user->can('cheques.deposit') && $this->withinCenter($user, $model);
    }

    /**
     * Accept or reject a bank deposit (30/09/2026) — the accountant's check
     * of the deposit receipt. Centre scope kept, like every other ability.
     */
    public function validateDeposit(User $user, Model $model): bool
    {
        return $user->can('cheques.validate-deposit') && $this->withinCenter($user, $model);
    }
}
