<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Context;

use App\Livewire\Backoffice\Employees\EmployeesIndex;
use App\Livewire\Backoffice\Groups\GroupsIndex;
use App\Livewire\Backoffice\Settings\SallesTab;
use App\Livewire\Backoffice\Users\UsersIndex;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Role;
use App\Models\Salle;
use App\Models\User;
use App\Services\Context\CurrentContext;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Every CRUD list must follow the center selected in the top-bar context
 * switcher: only the active center's rows (plus global NULL-center rows)
 * may appear, and the lists must refresh on `context-changed`.
 */
final class CenterScopingTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $rabat;

    private Etablissement $casa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create(['nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31', 'par_defaut' => true, 'inscription_ouverte' => true]);

        $this->rabat = Etablissement::factory()->create(['nom_centre' => 'GLS Rabat']);
        $this->casa = Etablissement::factory()->create(['nom_centre' => 'GLS Casablanca']);
    }

    private function globalUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    public function test_employees_list_is_scoped_to_the_selected_center(): void
    {
        $user = $this->globalUser();
        Employee::factory()->create(['nom' => 'EmployeRabatX', 'etablissement_id' => $this->rabat->id, 'user_id' => $user->id]);
        Employee::factory()->create(['nom' => 'EmployeCasaX', 'etablissement_id' => $this->casa->id, 'user_id' => $user->id]);

        app(CurrentContext::class)->setEtablissement($this->casa->id);
        Livewire::test(EmployeesIndex::class)
            ->assertSee('EmployeCasaX')
            ->assertDontSee('EmployeRabatX');
    }

    public function test_groups_list_and_tab_counts_are_scoped_to_the_selected_center(): void
    {
        $this->globalUser();
        Group::factory()->create(['nom' => 'GroupeRabatX', 'statut' => Group::STATUT_EN_FORMATION, 'etablissement_id' => $this->rabat->id, 'annee_scolaire_id' => $this->annee->id]);
        Group::factory()->create(['nom' => 'GroupeCasaX', 'statut' => Group::STATUT_EN_FORMATION, 'etablissement_id' => $this->casa->id, 'annee_scolaire_id' => $this->annee->id]);

        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        Livewire::test(GroupsIndex::class)
            ->assertSee('GroupeRabatX')
            ->assertDontSee('GroupeCasaX');
    }

    public function test_salles_tab_is_scoped_to_the_selected_center(): void
    {
        $this->globalUser();
        Salle::factory()->create(['nom' => 'SalleRabatX', 'etablissement_id' => $this->rabat->id]);
        Salle::factory()->create(['nom' => 'SalleCasaX', 'etablissement_id' => $this->casa->id]);

        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        Livewire::test(SallesTab::class)
            ->assertSee('SalleRabatX')
            ->assertDontSee('SalleCasaX');
    }

    public function test_users_list_follows_the_employee_center_but_keeps_admin_accounts(): void
    {
        $admin = $this->globalUser(); // no employee profile → visible everywhere

        $rUser = User::factory()->create(['name' => 'CompteRabatX']);
        Employee::factory()->create(['etablissement_id' => $this->rabat->id, 'user_id' => $rUser->id]);
        $cUser = User::factory()->create(['name' => 'CompteCasaX']);
        Employee::factory()->create(['etablissement_id' => $this->casa->id, 'user_id' => $cUser->id]);

        app(CurrentContext::class)->setEtablissement($this->rabat->id);
        Livewire::test(UsersIndex::class)
            ->assertSee('CompteRabatX')
            ->assertDontSee('CompteCasaX')
            ->assertSee($admin->name);
    }

}
