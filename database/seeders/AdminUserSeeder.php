<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

final class AdminUserSeeder extends Seeder
{
    /**
     * Seed the local development Backoffice administrator + its linked
     * Employee record (money operations require an employee identity for
     * agent_id / requested_by accountability).
     *
     * The account is granted the `super-admin` role here so a freshly seeded
     * database is immediately usable: without a role the user authenticates
     * but every `permission:`-protected route answers 403 ("User does not
     * have the right permissions"). This runs AFTER RolesAndPermissionsSeeder
     * (the role must exist) and AFTER ReferentialDataSeeder (so the employee
     * can be attached to the centers) — see DatabaseSeeder for the order.
     *
     * ⚠ Credentials come from the environment in production. Locally they
     * default to admin@gls.test / password; on any other environment
     * ADMIN_PASSWORD must be set explicitly or this seeder refuses to run,
     * so a deploy can never silently publish a well-known password.
     * The account is forced to change its password on first sign-in unless
     * it is the local dev default.
     */
    public function run(): void
    {
        $isLocal = app()->environment('local', 'testing');
        $email = (string) env('ADMIN_EMAIL', 'admin@gls.test');
        $password = env('ADMIN_PASSWORD');

        if ($password === null || $password === '') {
            if (! $isLocal) {
                $this->command?->error(
                    'ADMIN_PASSWORD is not set — refusing to seed an administrator '
                    .'with a default password outside local/testing.'
                );

                return;
            }

            $password = 'password';
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'GLS Admin',
                'username' => (string) env('ADMIN_USERNAME', 'admin'),
                'password' => Hash::make((string) $password),
                // A provisioned production admin must rotate its password;
                // the local dev default stays frictionless.
                'must_change_password' => ! $isLocal,
                // A deactivated user can never sign in (LoginRequest) — make
                // sure a re-seed never leaves the dev admin locked out.
                'is_active' => true,
            ],
        );

        // user_id is set explicitly, so EmployeeObserver does NOT generate
        // a second login for this employee.
        $employee = Employee::query()->firstOrNew(['user_id' => $user->id]);

        // Only on first creation: on a live database EMP-001 may already
        // belong to a real staff member, and an existing reference must never
        // be rewritten (it is printed on documents).
        if (! $employee->exists) {
            $employee->reference = ReferenceGenerator::make('EMP', 'employees');
        }

        $employee->fill(
            [
                'nom' => 'Admin',
                'prenom' => 'GLS',
                // Sans sexe, photoUrl() n'a pas d'avatar par défaut cohérent.
                'sexe' => 'Homme',
                'categorie' => Employee::CATEGORIE_DIRECTEUR,
                'statut' => Employee::STATUT_ACTIF,
                'email' => $email,
            ],
        )->save();

        // An employee must belong to at least one center (CLAUDE.md §16). The
        // dev admin gets every center, so the context switcher and the
        // center-scoped policies have something to work with.
        $etablissementIds = Etablissement::query()->pluck('id')->all();

        if ($etablissementIds !== [] && $employee->etablissements()->doesntExist()) {
            $employee->syncEtablissements($etablissementIds);
        }

        // Idempotent: assignRole() is a no-op when the role is already held.
        $superAdmin = Role::findOrCreate(Role::SUPER_ADMIN, 'web');

        if (! $user->hasRole($superAdmin)) {
            $user->assignRole($superAdmin);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
