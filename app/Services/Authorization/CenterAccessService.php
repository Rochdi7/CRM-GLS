<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Answers "on WHICH center's data may this user act?" — separate from
 * roles/permissions, which answer "WHAT may this user do?"
 * (docs/authorization-architecture.md).
 *
 * Rules:
 *  - `centers.access-all` (or super-admin via Gate::before) ⇒ every center;
 *  - otherwise every center the employee is assigned to via the
 *    `employee_etablissement` pivot (an employee may work in several
 *    centers; the `employees.etablissement_id` column is only its PRIMARY
 *    center and is included as a fallback for rows predating the pivot);
 *  - records with a NULL center are global: visible to anyone holding the
 *    module permission;
 *  - a user with no employee profile (and no access-all) is confined to
 *    global records.
 */
final class CenterAccessService
{
    public function hasGlobalAccess(User $user): bool
    {
        return $user->can('centers.access-all');
    }

    public function canAccessCenter(User $user, ?int $centerId): bool
    {
        if ($centerId === null || $this->hasGlobalAccess($user)) {
            return true;
        }

        return in_array($centerId, $this->accessibleCenterIds($user), true);
    }

    /**
     * Center ids this user is assigned to (empty when none — NOT "all").
     *
     * @return list<int>
     */
    public function accessibleCenterIds(User $user): array
    {
        $employee = $user->employee;

        if ($employee === null) {
            return [];
        }

        // Load once per request and reuse: this method is called from every
        // policy check and every center-scoped list query, so re-querying
        // the pivot each time would add an N+1 across the whole page.
        if (! $employee->relationLoaded('etablissements')) {
            $employee->load('etablissements:id');
        }

        $ids = $employee->etablissements->pluck('id')->all();

        // The primary column is authoritative for legacy rows that have no
        // pivot entry yet; harmless duplicate otherwise (deduped below).
        if ($employee->etablissement_id !== null) {
            $ids[] = $employee->etablissement_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Constrains a query to accessible centers (global NULL rows included).
     */
    public function scopeAccessibleCenters(Builder $query, User $user, string $column = 'etablissement_id'): Builder
    {
        if ($this->hasGlobalAccess($user)) {
            return $query;
        }

        $ids = $this->accessibleCenterIds($user);

        return $query->where(function (Builder $q) use ($ids, $column): void {
            $q->whereNull($column)->orWhereIn($column, $ids);
        });
    }
}
