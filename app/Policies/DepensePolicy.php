<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Depense;
use App\Models\User;
use App\Policies\Concerns\ResourcePolicy;
use Illuminate\Database\Eloquent\Model;

final class DepensePolicy extends ResourcePolicy
{
    protected string $module = 'expenses';

    // An expense reaches its center through the till it came out of.
    protected function centerId(Model $model): ?int
    {
        $id = $model->caisse?->etablissement_id;

        return $id === null ? null : (int) $id;
    }

    /**
     * Approve / refuse a pending expense — the decision that debits the till
     * (Domain\Expenses\Actions\ApprouverDepense).
     *
     * Requires `expenses.approve`, which sits in no role preset: in practice
     * only super-admins hold it (via Gate::before) unless a super-admin
     * grants it by hand.
     */
    public function approve(User $user, Depense $depense): bool
    {
        // An already-decided expense has no decision left to take; the Domain
        // actions re-check this under lock (defense in depth).
        if ($depense->isDecided()) {
            return false;
        }

        return $user->can('expenses.approve') && $this->withinCenter($user, $depense);
    }

    /**
     * A refused expense is closed history — nothing about it may be edited.
     * (An approved one stays editable exactly as before: its money already
     * moved, and UpdateDepenseRequest structurally excludes montant/caisse_id,
     * so an edit can never change the amount that left the till.)
     */
    public function update(User $user, Model $model): bool
    {
        if ($model instanceof Depense && $model->isRefusee()) {
            return false;
        }

        return parent::update($user, $model);
    }
}
