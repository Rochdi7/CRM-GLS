<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Auto-generated employee credentials (gls-crm-laravel-structure.md §5).
 *
 * Generates a unique username from the employee's name (e.g. "j.dupont"),
 * a random temporary password, creates the User row, links it back to the
 * Employee, and returns the plaintext password ONCE so it can be shown to
 * the admin creating the account. The plaintext password is never stored
 * or logged anywhere after this call.
 */
final class EmployeeCredentialService
{
    public function createCredentialsFor(Employee $employee): string
    {
        $username = $this->generateUniqueUsername($employee);
        $plainPassword = Str::password(12);

        $user = User::create([
            'name' => $employee->nomComplet(),
            'email' => $employee->email ?? "{$username}@gls-crm.local", // placeholder if no real email yet
            'username' => $username,
            'password' => $plainPassword, // hashed by the User model's 'hashed' cast
            'must_change_password' => true, // force reset on first login (middleware phase)
        ]);

        $employee->forceFill(['user_id' => $user->id])->saveQuietly();

        return $plainPassword;
    }

    private function generateUniqueUsername(Employee $employee): string
    {
        // `requestedUsername` is a transient property (not a real employees
        // column) set by EmployeeController::store() from the optional form
        // field — already validated as unique by StoreEmployeeRequest, but
        // re-checked here defensively before falling back to auto-generation.
        $requested = trim((string) ($employee->requestedUsername ?? ''));

        if ($requested !== '' && ! User::query()->where('username', $requested)->exists()) {
            return $requested;
        }

        $base = Str::lower(Str::substr($employee->prenom, 0, 1).'.'.Str::slug($employee->nom));
        $username = $base;
        $i = 1;

        while (User::query()->where('username', $username)->exists()) {
            $username = $base.$i++;
        }

        return $username;
    }
}
