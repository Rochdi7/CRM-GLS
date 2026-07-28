<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Livewire\Backoffice\Caisses\CaissesIndex;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\User;
use App\Services\CaisseProvisioner;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Caisses have NO manual CRUD: one till is provisioned automatically per
 * employee. This covers the read-only screen + the auto-provisioning rule.
 */
final class CaissesCrudTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    // --- Auto-provisioning --------------------------------------------------

    public function test_creating_an_employee_creates_its_cash_register(): void
    {
        $employee = Employee::factory()->create([
            'nom' => 'Bennani',
            'prenom' => 'Karim',
            'etablissement_id' => $this->centre->id,
        ]);

        $caisse = $employee->caisses()->first();

        $this->assertNotNull($caisse);
        $this->assertSame('Caisse — Karim Bennani', $caisse->nom);
        $this->assertSame($this->centre->id, $caisse->etablissement_id);
        $this->assertSame($employee->id, $caisse->responsable_employee_id);
        $this->assertEquals(0.0, (float) $caisse->solde);
        $this->assertSame(Caisse::STATUT_ACTIVE, $caisse->statut);
    }

    public function test_provisioning_is_idempotent(): void
    {
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);

        // The observer already created it; a second pass must not duplicate.
        $this->assertNull(app(CaisseProvisioner::class)->provisionFor($employee));
        $this->assertSame(1, $employee->caisses()->count());
    }

    public function test_the_backfill_command_provisions_employees_without_a_till(): void
    {
        $employee = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        // Simulate an employee predating the rule.
        $employee->caisses()->delete();
        $this->assertSame(0, $employee->caisses()->count());

        $this->artisan('caisses:provision')->assertSuccessful();

        $this->assertSame(1, $employee->fresh()->caisses()->count());
    }

    // --- Read-only screen ---------------------------------------------------

    public function test_index_renders_for_a_permitted_user(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('cash-registers.view');
        // Without centers.access-all the user only sees its own center's tills.
        Employee::factory()->create(['user_id' => $u->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($u->fresh());

        Caisse::factory()->create(['nom' => 'Caisse Principale', 'etablissement_id' => $this->centre->id]);

        Livewire::test(CaissesIndex::class)
            ->assertOk()
            ->assertSee('Caisse Principale');
    }

    public function test_index_is_forbidden_without_the_view_permission(): void
    {
        $u = User::factory()->create();
        $u->givePermissionTo('dashboard.view');
        $this->actingAs($u);

        Livewire::test(CaissesIndex::class)->assertForbidden();
    }

    public function test_the_screen_exposes_no_write_actions(): void
    {
        // A till is never added, renamed or deleted by hand.
        $this->assertFalse(method_exists(CaissesIndex::class, 'create'));
        $this->assertFalse(method_exists(CaissesIndex::class, 'edit'));
        $this->assertFalse(method_exists(CaissesIndex::class, 'save'));
        $this->assertFalse(method_exists(CaissesIndex::class, 'delete'));
    }

    public function test_no_write_routes_exist_for_cash_registers(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.caisses.create'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.caisses.store'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.caisses.edit'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.caisses.update'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('backoffice.caisses.destroy'));
    }

    public function test_the_search_matches_the_till_name_and_its_employee(): void
    {
        $this->admin();

        Employee::factory()->create([
            'nom' => 'Cherkaoui', 'prenom' => 'Salma', 'etablissement_id' => $this->centre->id,
        ]);
        Employee::factory()->create([
            'nom' => 'Alaoui', 'prenom' => 'Youssef', 'etablissement_id' => $this->centre->id,
        ]);

        Livewire::test(CaissesIndex::class)
            ->set('search', 'Cherkaoui')
            ->assertSee('Salma Cherkaoui')
            ->assertDontSee('Youssef Alaoui');
    }

    public function test_the_status_filter_narrows_the_list(): void
    {
        $this->admin();

        Caisse::factory()->create(['nom' => 'Caisse Active', 'etablissement_id' => $this->centre->id, 'statut' => Caisse::STATUT_ACTIVE]);
        Caisse::factory()->create(['nom' => 'Caisse Dormante', 'etablissement_id' => $this->centre->id, 'statut' => Caisse::STATUT_INACTIVE]);

        Livewire::test(CaissesIndex::class)
            ->set('statutFilter', Caisse::STATUT_INACTIVE)
            ->assertSee('Caisse Dormante')
            ->assertDontSee('Caisse Active');
    }

    public function test_the_list_is_scoped_to_the_users_center(): void
    {
        $autre = Etablissement::factory()->create();

        $u = User::factory()->create();
        $u->givePermissionTo('cash-registers.view');
        Employee::factory()->create(['user_id' => $u->id, 'etablissement_id' => $this->centre->id]);
        $this->actingAs($u->fresh());

        Caisse::factory()->create(['nom' => 'Caisse Ici', 'etablissement_id' => $this->centre->id]);
        Caisse::factory()->create(['nom' => 'Caisse Ailleurs', 'etablissement_id' => $autre->id]);

        Livewire::test(CaissesIndex::class)
            ->assertSee('Caisse Ici')
            ->assertDontSee('Caisse Ailleurs');
    }

    public function test_the_detail_page_renders(): void
    {
        $this->admin();

        $caisse = Caisse::factory()->create(['nom' => 'Caisse Détail', 'etablissement_id' => $this->centre->id]);

        $this->get(route('backoffice.caisses.show', $caisse))
            ->assertOk()
            ->assertSee('Caisse Détail');
    }
}
