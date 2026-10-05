<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Support\Authorization\PermissionRegistry;
use App\Support\Settings\AppSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotent — safe to run repeatedly, on the live database too:
 *
 *   php artisan db:seed --class=RolesAndPermissionsSeeder
 *
 * Permissions and roles are findOrCreate'd; an existing preset role is
 * MERGED with its code preset (see mergePreset()) so a re-seed never wipes
 * a permission the admin granted or revoked by hand on the Rôles screen.
 * `ROLES_SEED_RESET=1` forces every preset back to the code.
 *
 * Source of truth: App\Support\Authorization\PermissionRegistry.
 * Does NOT touch users: assigning the first super-admin is a separate,
 * explicit step (php artisan auth:assign-super-admin <email>).
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    /**
     * Force every preset role back to its code preset, discarding hand
     * edits made on the Rôles screen. Off by default; `ROLES_SEED_RESET=1`
     * on the command line does the same from a shell. Never set it in `.env`.
     */
    public static bool $resetPresets = false;

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionRegistry::names() as $name) {
            Permission::findOrCreate($name, self::GUARD);
        }

        $matrix = PermissionRegistry::matrix();
        $applied = AppSettings::options(AppSettings::ROLE_PRESETS_APPLIED);
        $reset = self::$resetPresets || filter_var(env('ROLES_SEED_RESET', false), FILTER_VALIDATE_BOOL);

        foreach (PermissionRegistry::roles() as $name => $label) {
            /** @var Role $role */
            $role = Role::findOrCreate($name, self::GUARD);

            if ($role->label !== $label) {
                $role->update(['label' => $label]);
            }

            $preset = $matrix[$name] ?? [];

            // super-admin keeps an EMPTY permission set on purpose —
            // Gate::before grants everything (docs/authorization-architecture.md).
            // A brand-new role, or an explicit reset, takes the preset as is.
            if ($name === Role::SUPER_ADMIN || $role->wasRecentlyCreated || $reset) {
                $role->syncPermissions($preset);
                $applied[$name] = array_values($preset);

                continue;
            }

            $role->syncPermissions($this->mergePreset(
                $name,
                $role->permissions()->pluck('name')->all(),
                $preset,
                $applied[$name] ?? null,
            ));
            $applied[$name] = array_values($preset);
        }

        AppSettings::setOptions(AppSettings::ROLE_PRESETS_APPLIED, $applied);

        $this->revokeGlobalCenterAccess();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * The permission set an EXISTING preset role ends up with after a re-seed.
     *
     * A re-seed must bring what the CODE changed since the last seed (a new
     * module's permissions, a preset deliberately narrowed) without undoing
     * what an ADMIN decided on the Rôles screen in between — the same rule
     * every other seeder follows (CLAUDE.md § « Seeders — production only »:
     * a seeder garnit, it never reopens what an admin closed). On 05/10/2026
     * the CEO had widened « Comptable » to 61 permissions by hand; the old
     * `syncPermissions($preset)` would have thrown that away on the next
     * `db:seed`, silently.
     *
     * Three-way merge against the preset recorded at the previous seed:
     *   - permissions the code ADDED to the preset since   → granted;
     *   - permissions the code REMOVED from the preset since → revoked;
     *   - everything else keeps the live value (hand grant or hand revoke).
     * With no record (a database seeded before this tracking existed) the
     * live set is kept and the whole preset is added — nothing an admin
     * granted can be lost; a hand REVOKE made before tracking is re-granted
     * once, which the seeder says out loud.
     * `superAdminOnly()` abilities are stripped whatever the source: no role
     * may hold them (§16), hand-granted or not.
     *
     * @param  list<string>  $live
     * @param  list<string>  $preset
     * @param  list<string>|null  $previous
     * @return list<string>
     */
    private function mergePreset(string $role, array $live, array $preset, ?array $previous): array
    {
        $added = $previous === null ? $preset : array_diff($preset, $previous);
        $removed = $previous === null ? [] : array_diff($previous, $preset);

        $result = array_values(array_unique([...array_diff($live, $removed), ...$added]));
        $result = array_values(array_diff($result, PermissionRegistry::superAdminOnly()));

        $kept = array_values(array_diff($result, $preset));
        $regranted = array_values(array_diff($added, $live));
        $revoked = array_values(array_intersect($removed, $live));

        if ($kept !== []) {
            $this->command?->line(sprintf('%s : %d permission(s) accordée(s) à la main conservée(s) (%s).', $role, count($kept), implode(', ', $kept)));
        }
        if ($regranted !== []) {
            $this->command?->line(sprintf('%s : %d permission(s) ajoutée(s) par le code (%s).', $role, count($regranted), implode(', ', $regranted)));
        }
        if ($revoked !== []) {
            $this->command?->warn(sprintf('%s : %d permission(s) retirée(s) par le code (%s).', $role, count($revoked), implode(', ', $revoked)));
        }

        return $result;
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
                'centers.access-all retiré de %d rôle(s) et %d utilisateur(s) - réservé aux super administrateurs.',
                $roles,
                $users,
            ));
        }
    }
}
