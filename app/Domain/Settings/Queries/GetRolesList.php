<?php

declare(strict_types=1);

namespace App\Domain\Settings\Queries;

use App\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-model for the Roles & Permissions list (Inertia/React migration,
 * docs/inertia-react-migration-plan.md Phase 7). Extracted from
 * App\Livewire\Backoffice\Roles\RolesIndex::render() — same query shape
 * (ilike search on name/label, orderBy('name'), withCount permissions+users).
 *
 * Domain placement: no dedicated "Roles"/"Authorization" Domain module exists
 * (PROJECT_INVENTORY.md §11 lists the module folders reserved so far —
 * Centers/Employees/Students/Registrations/Groups/Attendance/Finance/
 * Payments/Expenses/Stock/Reports/Settings — none named Roles/Authorization).
 * Roles & permissions are system/administrative configuration in the same
 * sense as établissements/années scolaires/salles (the other modules already
 * housed under the "Settings" tabbed page, CLAUDE.md §11), not a business
 * transaction module like Students or Finance — so this read-model is placed
 * under the existing `Domain\Settings` reserved folder rather than inventing
 * a new top-level Domain module for a single query class. If role/permission
 * management grows further (e.g. Domain actions for create/update/delete),
 * revisit whether a dedicated `Domain\Authorization` module is warranted.
 */
final class GetRolesList
{
    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function __invoke(string $search, int $perPage): LengthAwarePaginator
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('label', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Shape one Role row for the Inertia index prop — exposes `isProtected`
     * so the frontend can hide edit/delete actions for super-admin without
     * re-deriving the rule (Role::isProtected() stays the single source of
     * truth).
     *
     * @return array<string, mixed>
     */
    public static function present(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'label' => $role->displayLabel(),
            'isProtected' => $role->isProtected(),
            'permissionsCount' => $role->permissions_count,
            'usersCount' => $role->users_count,
        ];
    }
}
