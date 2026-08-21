<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Employee;
use App\Models\Role;
use App\Services\CaisseProvisioner;
use App\Services\EmployeeCredentialService;
use App\Support\Authorization\PermissionRegistry;

/**
 * Fires on Employee::create() no matter what triggers it (form, import,
 * seeder) — see gls-crm-laravel-structure.md §5 for why this is an observer
 * and not logic inside a form/controller.
 */
final class EmployeeObserver
{
    public function created(Employee $employee): void
    {
        // Every employee owns exactly one till — there is no manual caisse
        // creation screen. Idempotent, so a re-fire can't duplicate it.
        app(CaisseProvisioner::class)->provisionFor($employee);

        // Only auto-create credentials if this employee doesn't already have
        // one (protects against double-firing if user_id was set manually).
        if (is_null($employee->user_id)) {
            $plainPassword = app(EmployeeCredentialService::class)->createCredentialsFor($employee);

            // Flash the one-time password so the creating admin's UI can
            // display it once. NEVER persisted anywhere in plaintext.
            session()->flash('new_employee_password', $plainPassword);
            session()->flash('new_employee_username', $employee->fresh()->user?->username);

            $this->assignDefaultRole($employee);
        }
    }

    /**
     * Setting a real catégorie on an employee whose login is still
     * ROLE-LESS assigns the matching default role — the escape hatch for
     * accounts seeded/created as « Autre » (e.g. the hors-fichier GLS
     * mailboxes): the admin fixes the job title on the Employees screen and
     * the account starts working, no separate Autorisations trip needed.
     *
     * Only ever fills a VACUUM: a user who already holds ANY role is never
     * touched, so changing the catégorie of a normal employee re-derives
     * nothing — `categorie` still never drives access at runtime
     * (CLAUDE.md §16), and Autorisations remains the way to CHANGE access.
     */
    public function updated(Employee $employee): void
    {
        if ($employee->wasChanged('categorie')) {
            $this->assignDefaultRole($employee);
        }
    }

    /**
     * Gives the employee's login the default role for its job title
     * (PermissionRegistry::defaultRoleFor()) — without one the account
     * authenticates but every `permission:`-guarded page answers 403, a
     * dead end that looks like a broken deployment (this bit production
     * on 21/08/2026).
     *
     * Called from two places, both only to fill a vacuum:
     *  - created(): auto-created credentials only (inside the
     *    user_id-was-null branch) — when user_id is passed explicitly the
     *    caller owns the account and its roles (GlsStaffSeeder assigns its
     *    own, tests build users with exact permission sets);
     *  - updated(): when the catégorie changes and the login still has no
     *    role at all.
     * Guards, in every path:
     *  - a user holding ANY role is left untouched — a decision made on
     *    the Autorisations screen always wins;
     *  - « Autre » maps to no role — deliberately: no defined post ⇒ no
     *    access until someone decides;
     *  - the role row may not exist on a bare database (seeder not run):
     *    quietly skip rather than block the employee save.
     */
    private function assignDefaultRole(Employee $employee): void
    {
        $user = $employee->fresh()->user;

        if ($user === null || $user->roles()->exists()) {
            return;
        }

        $roleName = PermissionRegistry::defaultRoleFor((string) $employee->categorie);

        if ($roleName === null) {
            return;
        }

        $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->first();

        if ($role !== null) {
            $user->assignRole($role);
        }
    }
}
