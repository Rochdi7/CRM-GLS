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
     * By default the account is the CEO, Mohammed Rafik
     * (rafik@glszentrum.com) — the same address GlsStaffSeeder lists as
     * super-admin, so both seeders converge on ONE user/employee (both key on
     * the e-mail / user_id).
     * ⚠ His brother Amine Rafik (amine.rafik@glszentrum.com) is a separate
     * person with his own account, seeded by GlsStaffSeeder — never merge.
     *
     * ⚠ Point ADMIN_EMAIL somewhere else (a maintainer account on a server,
     * say) and you MUST set the matching ADMIN_NAME / ADMIN_NOM /
     * ADMIN_PRENOM / ADMIN_CATEGORIE too, or the account is created under the
     * CEO's name: the audit journal freezes `causer_label` at write time and
     * never rewrites it, so the developer's actions would read as the CEO's
     * forever. Leaving them unset keeps the historical defaults.
     *
     * ⚠ Credentials come from the environment in production. Locally they
     * default to rafik@glszentrum.com / password; on any other environment
     * ADMIN_PASSWORD must be set explicitly or this seeder refuses to run,
     * so a deploy can never silently publish a well-known password.
     * The account is forced to change its password on first sign-in unless
     * it is the local dev default.
     */
    public function run(): void
    {
        $isLocal = app()->environment('local', 'testing');
        $email = (string) env('ADMIN_EMAIL', 'rafik@glszentrum.com');
        $password = env('ADMIN_PASSWORD');

        // The identity follows ADMIN_EMAIL instead of being hard-coded: on a
        // server where ADMIN_EMAIL points at someone other than the CEO (the
        // maintainer account, say), a fixed name would freeze a WRONG
        // `causer_label` on every audit entry that account causes — and the
        // journal deliberately never rewrites a stored label afterwards
        // (docs/audit-journal.md). Defaults reproduce the previous values
        // exactly, so the local dev admin is unchanged.
        $nom = (string) env('ADMIN_NOM', 'Rafik');
        $prenom = (string) env('ADMIN_PRENOM', 'Mohammed');
        $name = (string) env('ADMIN_NAME', trim($prenom.' '.$nom));
        $sexe = (string) env('ADMIN_SEXE', 'Homme');
        $categorie = (string) env('ADMIN_CATEGORIE', Employee::CATEGORIE_DIRECTEUR);

        // A typo in .env must never write a value the app validates against
        // its model constants everywhere else (CLAUDE.md §11).
        if (! in_array($categorie, Employee::CATEGORIES, true)) {
            $this->command?->warn(
                "ADMIN_CATEGORIE « {$categorie} » is not a valid Employee catégorie — "
                .'falling back to '.Employee::CATEGORIE_DIRECTEUR.'.'
            );

            $categorie = Employee::CATEGORIE_DIRECTEUR;
        }

        if (! in_array($sexe, Employee::SEXES, true)) {
            $sexe = 'Homme';
        }

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
                'name' => $name,
                'username' => (string) env('ADMIN_USERNAME', 'rafik'),
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
        // withoutGlobalScopes() : ADMIN_EMAIL peut pointer sur le compte
        // technique masqué (HiddenAccountScope). Sans cela, firstOrNew() ne
        // retrouverait pas sa fiche et créerait un DOUBLON d'employé — et une
        // caisse de plus — à chaque exécution du seeder.
        $employee = Employee::query()
            ->withoutGlobalScopes()
            ->firstOrNew(['user_id' => $user->id]);

        // Only on first creation: on a live database EMP-001 may already
        // belong to a real staff member, and an existing reference must never
        // be rewritten (it is printed on documents).
        if (! $employee->exists) {
            $employee->reference = ReferenceGenerator::make('EMP', 'employees');
        }

        $employee->fill(
            [
                'nom' => $nom,
                'prenom' => $prenom,
                // Sans sexe, photoUrl() n'a pas d'avatar par défaut cohérent.
                'sexe' => $sexe,
                'categorie' => $categorie,
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
