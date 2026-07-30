<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Employee;
use App\Models\Etablissement;
use App\Models\Role;
use App\Models\TypeDepense;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TypeDepenseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Phase 6 (docs/phase-6-simple-crud-inventory.md §Q2) — Types de dépenses is
 * now its own standalone Inertia page (was the 3rd tab of the Livewire
 * Gestion des dépenses page). Replaces the retired
 * Livewire::test(TypesDepensesIndex::class) assertions entirely.
 */
final class TypesDepensesCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::SUPER_ADMIN);
        $this->actingAs($user);

        return $user;
    }

    /** A user holding only expense-types.* — no super-admin bypass. */
    private function withPermissions(string ...$permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        return $user;
    }

    // --- Access -------------------------------------------------------------

    public function test_the_page_renders_for_a_user_with_the_view_permission(): void
    {
        $this->withPermissions('expense-types.view');
        TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        $this->get(route('backoffice.types-depenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/TypesDepenses/Index')
                ->where('types.data.0.nom', 'Loyer')
            );
    }

    public function test_the_page_is_forbidden_without_the_view_permission(): void
    {
        $this->withPermissions('dashboard.view');

        $this->get(route('backoffice.types-depenses.index'))->assertForbidden();
    }

    public function test_creating_is_forbidden_without_the_create_permission(): void
    {
        $this->withPermissions('expense-types.view');

        $this->post(route('backoffice.types-depenses.store'), ['nom' => 'X', 'statut' => TypeDepense::STATUT_ACTIF])
            ->assertForbidden();
    }

    // --- Create / update ------------------------------------------------------

    public function test_a_custom_expense_type_can_be_created(): void
    {
        $this->withPermissions('expense-types.view', 'expense-types.create');

        $this->post(route('backoffice.types-depenses.store'), [
            'nom' => 'Fournitures de bureau', 'statut' => TypeDepense::STATUT_ACTIF,
        ])->assertRedirect(route('backoffice.types-depenses.index'));

        // The form NEVER creates a system type (schema §12).
        $this->assertDatabaseHas('types_depenses', [
            'nom' => 'Fournitures de bureau',
            'statut' => TypeDepense::STATUT_ACTIF,
            'is_system' => false,
        ]);
    }

    public function test_the_name_is_required_and_must_be_unique(): void
    {
        $this->admin();
        TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        $this->post(route('backoffice.types-depenses.store'), ['nom' => '', 'statut' => TypeDepense::STATUT_ACTIF])
            ->assertSessionHasErrors('nom');

        $this->post(route('backoffice.types-depenses.store'), ['nom' => 'Loyer', 'statut' => TypeDepense::STATUT_ACTIF])
            ->assertSessionHasErrors('nom');
    }

    public function test_the_status_must_be_a_known_value(): void
    {
        $this->admin();

        $this->post(route('backoffice.types-depenses.store'), ['nom' => 'Divers', 'statut' => 'Nimporte'])
            ->assertSessionHasErrors('statut');
    }

    public function test_a_custom_expense_type_can_be_updated(): void
    {
        $this->withPermissions('expense-types.view', 'expense-types.update');
        $type = TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        $this->put(route('backoffice.types-depenses.update', $type), [
            'nom' => 'Loyer du centre', 'statut' => TypeDepense::STATUT_INACTIF,
        ])->assertRedirect(route('backoffice.types-depenses.index'));

        $this->assertDatabaseHas('types_depenses', [
            'id' => $type->id,
            'nom' => 'Loyer du centre',
            'statut' => TypeDepense::STATUT_INACTIF,
        ]);
    }

    // --- System types are LOCKED ----------------------------------------------

    public function test_a_system_expense_type_cannot_be_edited_even_by_a_super_admin(): void
    {
        $this->admin();
        $this->seed(TypeDepenseSeeder::class);
        $system = TypeDepense::where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)->firstOrFail();

        $this->put(route('backoffice.types-depenses.update', $system), ['nom' => 'Piraté', 'statut' => TypeDepense::STATUT_ACTIF])
            ->assertForbidden();

        $this->assertDatabaseHas('types_depenses', ['id' => $system->id, 'nom' => TypeDepense::SYSTEM_PAIEMENT_PROF]);
    }

    public function test_a_system_expense_type_cannot_be_deleted(): void
    {
        $this->admin();
        $this->seed(TypeDepenseSeeder::class);
        $system = TypeDepense::where('nom', TypeDepense::SYSTEM_TRANSFERT_CAISSE)->firstOrFail();

        $this->delete(route('backoffice.types-depenses.destroy', $system))->assertForbidden();

        $this->assertDatabaseHas('types_depenses', ['id' => $system->id]);
    }

    public function test_system_rows_are_marked_in_the_props_but_expose_no_forgeable_is_system_field(): void
    {
        $this->admin();
        $this->seed(TypeDepenseSeeder::class);

        $this->get(route('backoffice.types-depenses.index'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'types.data',
                fn ($rows) => collect($rows)->contains(fn ($row) => $row['nom'] === TypeDepense::SYSTEM_PAIEMENT_PROF && $row['isSystem'] === true),
            ));
    }

    public function test_is_system_cannot_be_forged_from_client_input_on_create(): void
    {
        $this->withPermissions('expense-types.view', 'expense-types.create');

        $this->post(route('backoffice.types-depenses.store'), [
            'nom' => 'Faux système', 'statut' => TypeDepense::STATUT_ACTIF, 'is_system' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('types_depenses', ['nom' => 'Faux système', 'is_system' => false]);
    }

    // --- Delete ---------------------------------------------------------------

    public function test_deleting_is_blocked_when_the_type_is_used_by_expenses(): void
    {
        $this->admin();
        $type = TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        $centre = Etablissement::factory()->create();
        $caisse = Caisse::factory()->create(['etablissement_id' => $centre->id]);
        $agent = Employee::factory()->create(['etablissement_id' => $centre->id]);

        Depense::create([
            'reference' => 'DEP-000001',
            'type_depense_id' => $type->id,
            'caisse_id' => $caisse->id,
            'montant' => 500,
            'date_depense' => '2026-01-15',
            'agent_id' => $agent->id,
        ]);

        $this->delete(route('backoffice.types-depenses.destroy', $type))->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('types_depenses', ['id' => $type->id]);
    }

    public function test_an_unused_custom_expense_type_can_be_deleted(): void
    {
        $this->withPermissions('expense-types.view', 'expense-types.delete');
        $type = TypeDepense::create(['nom' => 'Obsolète', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        $this->delete(route('backoffice.types-depenses.destroy', $type))->assertRedirect();

        $this->assertDatabaseMissing('types_depenses', ['id' => $type->id]);
    }

    public function test_deleting_is_forbidden_without_the_delete_permission(): void
    {
        $this->withPermissions('expense-types.view');
        $type = TypeDepense::create(['nom' => 'Obsolète', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        $this->delete(route('backoffice.types-depenses.destroy', $type))->assertForbidden();

        $this->assertDatabaseHas('types_depenses', ['id' => $type->id]);
    }

    // --- Search / pagination ---------------------------------------------------

    public function test_the_list_can_be_searched_by_name(): void
    {
        $this->admin();
        TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);
        TypeDepense::create(['nom' => 'Électricité', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        $this->get(route('backoffice.types-depenses.index', ['search' => 'Loyer']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('types.data.0.nom', 'Loyer')
                ->where('types.total', 1)
            );
    }

    public function test_types_depenses_index_is_served_by_its_own_controller_not_a_redirect(): void
    {
        $action = app('router')->getRoutes()->getByName('backoffice.types-depenses.index')?->getActionName();

        $this->assertSame('App\Http\Controllers\Backoffice\TypeDepenseController@index', $action);
    }

    public function test_the_depenses_management_page_no_longer_has_a_types_tab(): void
    {
        $this->withPermissions('expenses.view');

        $this->get(route('backoffice.depenses.index'))
            ->assertOk()
            ->assertDontSee('tab-types', false);
    }
}
