<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\CaisseProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * One demo login per role so every permission profile can be tested by hand
 * (password: « password » for all of them):
 *
 *   directeur@gls.test    → director               (all centers)
 *   operations@gls.test   → operations-director    (all centers)
 *   assistante@gls.test   → administrative-assistant (center-scoped!)
 *   enseignant@gls.test   → teacher
 *   marketing@gls.test    → marketing-manager
 *
 * Each gets a linked Employee (money operations need an agent identity) and
 * its auto-provisioned till.
 *
 * ⚠ Local development credentials only — never run in production.
 */
final class DemoRoleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $centre = Etablissement::query()->orderBy('id')->first();

        if ($centre === null) {
            $this->command?->error('Run ReferentialDataSeeder first (centers).');

            return;
        }

        $profiles = [
            ['directeur@gls.test', 'Directeur Démo', 'director', Employee::CATEGORIE_DIRECTEUR],
            ['operations@gls.test', 'Opérations Démo', 'operations-director', Employee::CATEGORIE_DIRECTEUR_OPERATIONS],
            ['assistante@gls.test', 'Assistante Démo', 'administrative-assistant', Employee::CATEGORIE_ASSISTANTE_ADMINISTRATIVE],
            ['enseignant@gls.test', 'Enseignant Démo', 'teacher', Employee::CATEGORIE_ENSEIGNANT],
            ['marketing@gls.test', 'Marketing Démo', 'marketing-manager', Employee::CATEGORIE_RESPONSABLE_MARKETING],
        ];

        $provisioner = app(CaisseProvisioner::class);

        foreach ($profiles as $i => [$email, $name, $role, $categorie]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => str($email)->before('@')->toString(),
                    'password' => Hash::make('password'),
                    'must_change_password' => false,
                    'is_active' => true,
                ],
            );

            $user->syncRoles([$role]);

            // user_id set explicitly → EmployeeObserver does NOT generate a
            // second login for these demo employees.
            [$prenom, $nom] = explode(' ', $name, 2);
            $employee = Employee::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'reference' => sprintf('EMP-ROLE%02d', $i + 1),
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'categorie' => $categorie,
                    'statut' => Employee::STATUT_ACTIF,
                    'email' => $email,
                    'etablissement_id' => $centre->id,
                ],
            );

            // Observer may be off (DatabaseSeeder runs WithoutModelEvents).
            $provisioner->provisionFor($employee);
        }

        $this->command?->info('Demo role users seeded (password: « password »).');
    }
}
