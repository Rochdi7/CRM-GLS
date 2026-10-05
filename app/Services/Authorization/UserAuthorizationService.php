<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Models\Role;
use App\Models\User;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

/**
 * All sensitive role/permission mutations go through here (never straight
 * from a Livewire component) — transactions, super-admin invariants and
 * audit logging in one place (docs/roles-and-permissions.md).
 *
 * Super-admin invariants enforced:
 *  - only a super-admin may grant or remove the super-admin role;
 *  - the system always keeps at least one super-admin;
 *  - direct permissions require users.assign-permissions.
 */
final class UserAuthorizationService
{
    /**
     * @param  list<string>  $roleNames  role machine names
     * @param  list<string>  $directPermissions  permission machine names
     */
    public function syncAuthorization(User $actor, User $target, array $roleNames, array $directPermissions = []): void
    {
        // Nobody edits their own authorization — a director could otherwise
        // grant himself the union of every non-super-admin preset (SEC-04).
        // Enforced here too so non-HTTP callers get the same rule.
        if ($actor->is($target)) {
            throw ValidationException::withMessages([
                'roles' => __('Vous ne pouvez pas modifier vos propres autorisations.'),
            ]);
        }

        $this->guardRoleNames($roleNames);
        $this->guardPermissionNames($actor, $target, $directPermissions);
        $this->guardSuperAdminRules($actor, $target, $roleNames);

        if ($directPermissions !== [] && ! $actor->can('users.assign-permissions')) {
            throw ValidationException::withMessages([
                'permissions' => __('Vous ne pouvez pas attribuer de permissions directes.'),
            ]);
        }

        $before = [
            'roles' => $target->roles()->pluck('name')->all(),
            'direct_permissions' => $target->permissions()->pluck('name')->all(),
        ];

        DB::transaction(function () use ($target, $roleNames, $directPermissions, $actor): void {
            $target->syncRoles($roleNames);

            if ($actor->can('users.assign-permissions')) {
                $target->syncPermissions($directPermissions);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity('authorization')
            ->causedBy($actor)
            ->performedOn($target)
            ->withProperties([
                'old' => $before,
                'new' => [
                    'roles' => $roleNames,
                    'direct_permissions' => $directPermissions,
                ],
            ])
            ->log('authorization updated');
    }

    /**
     * @param  list<string>  $roleNames
     */
    private function guardSuperAdminRules(User $actor, User $target, array $roleNames): void
    {
        $targetIsSuperAdmin = $target->hasRole(Role::SUPER_ADMIN);
        $willBeSuperAdmin = in_array(Role::SUPER_ADMIN, $roleNames, true);

        // Granting or removing super-admin requires being a super-admin.
        if (($willBeSuperAdmin !== $targetIsSuperAdmin) && ! $actor->hasRole(Role::SUPER_ADMIN)) {
            throw ValidationException::withMessages([
                'roles' => __('Seul un super administrateur peut attribuer ou retirer le rôle super administrateur.'),
            ]);
        }

        // Never remove the LAST super-admin (lockout prevention).
        if ($targetIsSuperAdmin && ! $willBeSuperAdmin && $this->superAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'roles' => __('Impossible de retirer le dernier super administrateur du système.'),
            ]);
        }
    }

    /**
     * @param  list<string>  $roleNames
     */
    private function guardRoleNames(array $roleNames): void
    {
        $known = Role::query()->pluck('name')->all();

        foreach ($roleNames as $name) {
            if (! in_array($name, $known, true)) {
                throw ValidationException::withMessages([
                    'roles' => __('Rôle inconnu : :name', ['name' => $name]),
                ]);
            }
        }
    }

    /**
     * May this actor hand out a `superAdminOnly()` ability as a DIRECT
     * permission? Only a super-admin: those abilities (deletes, expense
     * approval, system settings…) are kept off every role on purpose (§16),
     * and "delegated by hand to one user" is a super-admin's decision. A
     * director holding `users.assign-permissions` must not be able to grant
     * a colleague `expenses.approve` or `payments.delete` that no role —
     * his own included — may carry. One of the `hasRole()` uses CLAUDE.md
     * §16 allows (super-admin invariants of this service).
     */
    public function canGrantReservedPermissions(User $actor): bool
    {
        return $actor->hasRole(Role::SUPER_ADMIN);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function guardPermissionNames(User $actor, User $target, array $permissions): void
    {
        $reserved = PermissionRegistry::superAdminOnly();
        // A reserved permission the target ALREADY holds was granted by a
        // super-admin: a later edit by a director may keep (or drop) it,
        // only a NEW reserved grant needs the super-admin.
        $alreadyHeld = $target->permissions()->pluck('name')->all();

        foreach ($permissions as $name) {
            if (in_array($name, $reserved, true)
                && ! in_array($name, $alreadyHeld, true)
                && ! $this->canGrantReservedPermissions($actor)) {
                throw ValidationException::withMessages([
                    'permissions' => __('The permission :name is reserved to the super-admin: only a super administrator can grant it directly.', ['name' => $name]),
                ]);
            }

            // « Tous les centres » is super-admin only (Gate::before) — the
            // ability can never be handed to anyone, in any way.
            if ($name === PermissionRegistry::GLOBAL_CENTER_ACCESS) {
                throw ValidationException::withMessages([
                    'permissions' => __("L'accès à tous les centres est réservé aux super administrateurs et ne peut pas être attribué."),
                ]);
            }

            if (! PermissionRegistry::exists($name)) {
                throw ValidationException::withMessages([
                    'permissions' => __('Permission inconnue : :name', ['name' => $name]),
                ]);
            }
        }
    }

    public function superAdminCount(): int
    {
        return User::query()->role(Role::SUPER_ADMIN)->count();
    }
}
