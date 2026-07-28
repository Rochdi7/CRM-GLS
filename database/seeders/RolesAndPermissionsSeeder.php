<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Database\Seeder;
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
