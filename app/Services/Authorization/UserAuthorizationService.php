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
        $this->guardRoleNames($roleNames);
        $this->guardPermissionNames($directPermissions);
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
     * @param  list<string>  $permissions
     */
    private function guardPermissionNames(array $permissions): void
    {
        foreach ($permissions as $name) {
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
