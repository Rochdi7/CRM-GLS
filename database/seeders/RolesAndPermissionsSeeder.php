<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent — safe to run repeatedly (findOrCreate + syncPermissions):
 *
 *   php artisan db:seed --class=RolesAndPermissionsSeeder
 *
 * Source of truth: App\Support\Authorization\PermissionRegistry.
 * Does NOT touch users: assigning the first super-admin is a separate,
 * explicit step (php artisan auth:assign-super-admin <email>).
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionRegistry::names() as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        $matrix = PermissionRegistry::matrix();

        foreach (PermissionRegistry::roles() as $name => $label) {
            /** @var Role $role */
            $role = Role::findOrCreate($name, self::GUARD);

            if ($role->label !== $label) {
                $role->update(['label' => $label]);
            }

            // super-admin keeps an EMPTY permission set on purpose —
            // Gate::before grants everything (docs/authorization-architecture.md).
            $role->syncPermissions($matrix[$name] ?? []);
        }

        $this->revokeGlobalCenterAccess();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * « Tous les centres » is super-admin only (Gate::before). The ability
     * used to be hand-grantable and CUSTOM roles are not re-synced above, so
     * a stale grant could survive on a live database — strip it from every
     * role and every user's direct permissions, every run (idempotent).
     */
    private function revokeGlobalCenterAccess(): void
    {
        $permission = Permission::findByName(PermissionRegistry::GLOBAL_CENTER_ACCESS, self::GUARD);

        $roles = DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
        $users = DB::table('model_has_permissions')->where('permission_id', $permission->id)->delete();

        if ($roles + $users > 0) {
            $this->command?->warn(sprintf(
                'centers.access-all retiré de %d rôle(s) et %d utilisateur(s) — réservé aux super administrateurs.',
                $roles,
                $users,
            ));
        }
    }
}
