<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Employee;
use App\Services\CaisseProvisioner;
use App\Services\EmployeeCredentialService;

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
        }
    }
}
