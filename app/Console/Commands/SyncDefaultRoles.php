<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Role;
use App\Support\Authorization\PermissionRegistry;
use Illuminate\Console\Command;

/**
 * One-shot bulk repair for role-less employee logins:
 *
 *   php artisan auth:sync-default-roles          # assign
 *   php artisan auth:sync-default-roles --dry-run # report only
 *
 * Walks every employee and, when its login holds NO role at all, assigns the
 * default role for the job title (PermissionRegistry::defaultRoleFor() — the
 * same map EmployeeObserver and GlsStaffSeeder use). Complements the
 * observer, which covers creations and catégorie edits going forward; this
 * command catches everything already in the database — a restored dump, an
 * import, accounts seeded before the auto-role fix.
 *
 * Same vacuum-only rule as everywhere else: a user holding ANY role is never
 * touched (Autorisations decisions always win), « Autre » gets nothing, and
 * running it twice changes nothing. Assignments are written to the audit
 * journal (`authorization` log) like every other role mutation.
 */
final class SyncDefaultRoles extends Command
{
    protected $signature = 'auth:sync-default-roles {--dry-run : Report what would change without writing}';

    protected $description = "Assign each role-less employee login the default role for its catégorie";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $assigned = [];
        $skipped = [];

        Employee::query()
            ->with(['user.roles'])
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get()
            ->each(function (Employee $employee) use ($dryRun, &$assigned, &$skipped): void {
                $user = $employee->user;

                if ($user === null) {
                    return;
                }

                $roleName = PermissionRegistry::defaultRoleFor((string) $employee->categorie);

                // « Responsable de système » ALWAYS holds super-admin (same
                // rule as EmployeeObserver) — every other catégorie only
                // fills a vacuum: an existing role is an Autorisations
                // decision that wins.
                if ($roleName === Role::SUPER_ADMIN && $user->hasRole(Role::SUPER_ADMIN)) {
                    return;
                }

                if ($roleName !== Role::SUPER_ADMIN && $user->roles->isNotEmpty()) {
                    return;
                }

                if ($roleName === null) {
                    // « Autre » / unknown post: nothing to derive — needs a
                    // real catégorie (Employees screen) or a manual grant.
                    $skipped[] = [$employee->reference, $user->email ?? $user->username, $employee->categorie];

                    return;
                }

                $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

                if ($role === null) {
                    $this->error("Role [{$roleName}] missing — run db:seed --class=RolesAndPermissionsSeeder first.");

                    return;
                }

                if (! $dryRun) {
                    $user->assignRole($role);

                    activity('authorization')
                        ->performedOn($user)
                        ->withProperties(['role' => $roleName, 'via' => 'console', 'command' => 'auth:sync-default-roles'])
                        ->log('default role assigned');
                }

                $assigned[] = [$employee->reference, $user->email ?? $user->username, $employee->categorie, $roleName];
            });

        if ($assigned !== []) {
            $this->info($dryRun ? 'Would assign:' : 'Assigned:');
            $this->table(['Référence', 'Login', 'Catégorie', 'Rôle'], $assigned);
        }

        if ($skipped !== []) {
            $this->warn('No default role for these (set a real catégorie on the Employees screen, or grant a role on Autorisations):');
            $this->table(['Référence', 'Login', 'Catégorie'], $skipped);
        }

        if ($assigned === [] && $skipped === []) {
            $this->info('Every employee login already has a role — nothing to do.');
        }

        return self::SUCCESS;
    }
}
