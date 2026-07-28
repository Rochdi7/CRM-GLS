<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class AdminUserSeeder extends Seeder
{
    /**
     * Seed the local development Backoffice administrator + its linked
     * Employee record (money operations require an employee identity for
     * agent_id / requested_by accountability).
     *
     * ⚠ Local development credentials only — replace with real user
     * provisioning (roles & permissions phase) before any deployment.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@gls.test'],
            [
                'name' => 'GLS Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'must_change_password' => false,
            ],
        );

        // user_id is set explicitly, so EmployeeObserver does NOT generate
        // a second login for this employee.
        Employee::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'reference' => 'EMP-000001',
                'nom' => 'Admin',
                'prenom' => 'GLS',
                'categorie' => Employee::CATEGORIE_DIRECTEUR,
                'statut' => Employee::STATUT_ACTIF,
                'email' => 'admin@gls.test',
            ],
        );
    }
}