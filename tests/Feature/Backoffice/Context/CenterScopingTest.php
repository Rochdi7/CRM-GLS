<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Context;

use App\Livewire\Backoffice\Settings\SallesTab;
use App\Livewire\Backoffice\Users\UsersIndex;
use App\Models\Employee;
use App\Models\Etablissement;
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
 * may appear. Covers only the modules whose Inertia-side test suite does
 * not yet independently assert this (Employees/Salles/Users) — Students/
 * Groups/Inscriptions had their own scenarios here superseded and removed
 * once their Livewire components were deleted (docs/phase-11-test-
 * coverage-mapping.md).
 */
final class CenterScopingTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $rabat;

    private Etablissement $casa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

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
