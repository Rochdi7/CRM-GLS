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
     * A login the observer just created gets the default role for the
     * employee's job title (PermissionRegistry::defaultRoleFor()) — without
     * one the account authenticates but every `permission:`-guarded page
     * answers 403, a dead end that looks like a broken deployment (this
     * bit production on 21/08/2026).
     *
     * Scope — auto-created credentials ONLY (inside the user_id-was-null
     * branch): when user_id is passed explicitly the caller owns the
     * account and its roles (GlsStaffSeeder assigns its own, tests build
     * users with exact permission sets). Creation-time default only:
     *  - « Autre » maps to no role — deliberately: no defined post ⇒ no
     *    access until someone decides on the Autorisations screen;
     *  - editing the catégorie later does NOT re-fire this (observer is
     *    `created` only) — `categorie` never drives access at runtime
     *    (CLAUDE.md §16), it only picks the starting role here;
     *  - the role row may not exist on a bare database (seeder not run):
     *    quietly skip rather than block the employee creation.
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
