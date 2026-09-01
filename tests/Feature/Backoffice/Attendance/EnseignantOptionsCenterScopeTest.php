<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Attendance;

use App\Domain\Attendance\Queries\GetCreneauFormOptions;
use App\Domain\Attendance\Queries\GetSeanceFormOptions;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The « Employé » dropdown of « Saisir l'absence » (and of the emploi du
 * temps) must offer the teachers of the ACTIVE centre only.
 *
 * Reported 01/09/2026: working in GLS Marrakech, the picker listed the
 * teachers of all seven branches — the query filtered on catégorie/statut
 * and nothing else, so it ignored the top-bar centre switcher entirely
 * (CLAUDE.md §11 « Context scoping is MANDATORY on every screen »).
 */
final class EnseignantOptionsCenterScopeTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $marrakech;

    private Etablissement $rabat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->marrakech = Etablissement::factory()->create();
        $this->rabat = Etablissement::factory()->create();
    }

    private function enseignant(string $nom, ?Etablissement $primary, Etablissement ...$assigned): Employee
    {
        $employee = Employee::factory()->create([
            'nom' => $nom,
            'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_ACTIF,
            'etablissement_id' => $primary?->id,
        ]);

        if ($assigned !== []) {
            $employee->syncEtablissements(array_map(fn (Etablissement $e): int => $e->id, $assigned));
        }

        return $employee->fresh();
    }

    /** @return list<string> */
    private function labelsFor(User $user): array
    {
        return array_column(app(GetSeanceFormOptions::class)->enseignants($user), 'label');
    }

    /**
     * A super-admin (global centre access) signed in with the top-bar
     * switcher pointed at $active. `setEtablissement()` reads auth()->user()
     * and refuses when nobody is signed in, so the login must come first.
     */
    private function superAdminIn(?Etablissement $active): User
    {
        $user = User::factory()->create();
        $user->assignRole('super-admin');
        $user = $user->fresh();
        $this->actingAs($user);
        app(CurrentContext::class)->setEtablissement($active?->id);

        return $user;
    }

    public function test_only_teachers_of_the_active_center_are_offered(): void
    {
        $ici = $this->enseignant('Marrakchi', $this->marrakech);
        $ailleurs = $this->enseignant('Rbati', $this->rabat);

        $labels = $this->labelsFor($this->superAdminIn($this->marrakech));

        $this->assertContains($ici->nomComplet(), $labels);
        $this->assertNotContains($ailleurs->nomComplet(), $labels, 'A teacher of another centre must not be offered.');
    }

    public function test_a_teacher_assigned_through_the_pivot_stays_offered(): void
    {
        // Primary centre is Rabat, but they also teach in Marrakech — reach
        // follows the employee_etablissement pivot (CLAUDE.md §16), so
        // scoping on the primary column alone would wrongly drop them.
        $partage = $this->enseignant('Partage', $this->rabat, $this->rabat, $this->marrakech);

        $this->assertContains($partage->nomComplet(), $this->labelsFor($this->superAdminIn($this->marrakech)));
    }

    public function test_a_center_less_teacher_stays_offered_everywhere(): void
    {
        $global = $this->enseignant('Global', null);

        $this->assertContains($global->nomComplet(), $this->labelsFor($this->superAdminIn($this->marrakech)));
    }

    public function test_non_teachers_and_inactive_teachers_are_never_offered(): void
    {
        $comptable = Employee::factory()->create([
            'nom' => 'Comptable', 'categorie' => Employee::CATEGORIE_COMPTABLE,
            'statut' => Employee::STATUT_ACTIF, 'etablissement_id' => $this->marrakech->id,
        ]);
        $inactif = Employee::factory()->create([
            'nom' => 'Inactif', 'categorie' => Employee::CATEGORIE_ENSEIGNANT,
            'statut' => Employee::STATUT_INACTIF, 'etablissement_id' => $this->marrakech->id,
        ]);

        $labels = $this->labelsFor($this->superAdminIn($this->marrakech));

        $this->assertNotContains($comptable->nomComplet(), $labels);
        $this->assertNotContains($inactif->nomComplet(), $labels);
    }

    public function test_all_centers_narrows_to_the_centers_the_user_reaches(): void
    {
        $ici = $this->enseignant('Marrakchi', $this->marrakech);
        $ailleurs = $this->enseignant('Rbati', $this->rabat);

        // A NON super-admin on a null active centre: no centre to narrow to,
        // so the fallback is their own reach — never every branch.
        $user = User::factory()->create();
        $user->givePermissionTo('attendance.view');
        $employee = Employee::factory()->create([
            'etablissement_id' => $this->marrakech->id,
            'user_id' => $user->id,
        ]);
        $employee->syncEtablissements([$this->marrakech->id]);
        $user = $user->fresh();
        $this->actingAs($user);
        // Refused for a non-global user (they keep their centre), so the
        // context stays on Marrakech either way — what matters is that the
        // fallback branch never widens to every branch.
        app(CurrentContext::class)->setEtablissement(null);

        $labels = $this->labelsFor($user);

        $this->assertContains($ici->nomComplet(), $labels);
        $this->assertNotContains($ailleurs->nomComplet(), $labels);
    }

    public function test_the_emploi_du_temps_picker_is_scoped_the_same_way(): void
    {
        $ici = $this->enseignant('Marrakchi', $this->marrakech);
        $ailleurs = $this->enseignant('Rbati', $this->rabat);
        $user = $this->superAdminIn($this->marrakech);

        $labels = array_column(app(GetCreneauFormOptions::class)->enseignants($user), 'label');

        $this->assertContains($ici->nomComplet(), $labels);
        $this->assertNotContains($ailleurs->nomComplet(), $labels);
    }
}
