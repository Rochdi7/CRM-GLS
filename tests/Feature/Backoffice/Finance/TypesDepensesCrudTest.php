<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Livewire\Backoffice\TypesDepenses\TypesDepensesIndex;
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
use Livewire\Livewire;
use Tests\TestCase;

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

        Livewire::test(TypesDepensesIndex::class)
            ->assertOk()
            ->assertSee('Loyer');
    }

    public function test_the_page_is_forbidden_without_the_view_permission(): void
    {
        $this->withPermissions('dashboard.view');

        Livewire::test(TypesDepensesIndex::class)->assertForbidden();
    }

    public function test_creating_is_forbidden_without_the_create_permission(): void
    {
        $this->withPermissions('expense-types.view');

        Livewire::test(TypesDepensesIndex::class)
            ->call('create')
            ->assertForbidden();
    }

    // --- Create / update ----------------------------------------------------

    public function test_a_custom_expense_type_can_be_created(): void
    {
        $this->withPermissions('expense-types.view', 'expense-types.create');

        Livewire::test(TypesDepensesIndex::class)
            ->call('create')
            ->set('nom', 'Fournitures de bureau')
            ->set('statut', TypeDepense::STATUT_ACTIF)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false);

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

        Livewire::test(TypesDepensesIndex::class)
            ->call('create')
            ->set('nom', '')
            ->call('save')
            ->assertHasErrors(['nom' => 'required'])
            ->set('nom', 'Loyer')
            ->call('save')
            ->assertHasErrors(['nom' => 'unique']);
    }

    public function test_the_status_must_be_a_known_value(): void
    {
        $this->admin();

        Livewire::test(TypesDepensesIndex::class)
            ->call('create')
            ->set('nom', 'Divers')
            ->set('statut', 'Nimporte')
            ->call('save')
            ->assertHasErrors('statut');
    }

    public function test_a_custom_expense_type_can_be_updated(): void
    {
        $this->withPermissions('expense-types.view', 'expense-types.update');
        $type = TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        Livewire::test(TypesDepensesIndex::class)
            ->call('edit', $type->id)
            ->assertSet('nom', 'Loyer')
            ->set('nom', 'Loyer du centre')
            ->set('statut', TypeDepense::STATUT_INACTIF)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('types_depenses', [
            'id' => $type->id,
            'nom' => 'Loyer du centre',
            'statut' => TypeDepense::STATUT_INACTIF,
        ]);
    }

    // --- System types are LOCKED -------------------------------------------

    public function test_a_system_expense_type_cannot_be_edited_even_by_a_super_admin(): void
    {
        $this->admin();
        $this->seed(TypeDepenseSeeder::class);
        $system = TypeDepense::where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)->firstOrFail();

        // Server-side rejection: the policy refuses update on is_system rows.
        Livewire::test(TypesDepensesIndex::class)
            ->call('edit', $system->id)
            ->assertForbidden();
    }

    public function test_a_system_expense_type_cannot_be_saved_through_a_forged_editing_id(): void
    {
        $this->admin();
        $this->seed(TypeDepenseSeeder::class);
        $system = TypeDepense::where('nom', TypeDepense::SYSTEM_SALAIRE)->firstOrFail();

        // save() re-authorizes — setting editingId directly is refused.
        Livewire::test(TypesDepensesIndex::class)
            ->set('editingId', $system->id)
            ->set('nom', 'Salaire piraté')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseHas('types_depenses', [
            'id' => $system->id,
            'nom' => TypeDepense::SYSTEM_SALAIRE,
        ]);
    }

    public function test_a_system_expense_type_cannot_be_deleted(): void
    {
        $this->admin();
        $this->seed(TypeDepenseSeeder::class);
        $system = TypeDepense::where('nom', TypeDepense::SYSTEM_TRANSFERT_CAISSE)->firstOrFail();

        Livewire::test(TypesDepensesIndex::class)
            ->call('delete', $system->id)
            ->assertForbidden();

        $this->assertDatabaseHas('types_depenses', ['id' => $system->id]);
    }

    public function test_the_system_row_shows_a_lock_badge_and_no_row_actions(): void
    {
        $this->admin();
        $this->seed(TypeDepenseSeeder::class);

        $component = Livewire::test(TypesDepensesIndex::class)
            ->assertSee(TypeDepense::SYSTEM_PAIEMENT_PROF)
            // Lock badge (label goes through __('System') → "Système" in fr.json).
            ->assertSeeHtml('ti ti-lock');

        // No edit/delete wired for the locked row.
        $system = TypeDepense::where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)->firstOrFail();
        $this->assertStringNotContainsString("edit({$system->id})", $component->html());
        $this->assertStringNotContainsString("delete({$system->id})", $component->html());
    }

    // --- Delete -------------------------------------------------------------

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

        Livewire::test(TypesDepensesIndex::class)
            ->call('delete', $type->id)
            ->assertHasErrors('delete');

        $this->assertDatabaseHas('types_depenses', ['id' => $type->id]);
    }

    public function test_an_unused_custom_expense_type_can_be_deleted(): void
    {
        $this->withPermissions('expense-types.view', 'expense-types.delete');
        $type = TypeDepense::create(['nom' => 'Obsolète', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        Livewire::test(TypesDepensesIndex::class)
            ->call('delete', $type->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('types_depenses', ['id' => $type->id]);
    }

    public function test_deleting_is_forbidden_without_the_delete_permission(): void
    {
        $this->withPermissions('expense-types.view');
        $type = TypeDepense::create(['nom' => 'Obsolète', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        Livewire::test(TypesDepensesIndex::class)
            ->call('delete', $type->id)
            ->assertForbidden();

        $this->assertDatabaseHas('types_depenses', ['id' => $type->id]);
    }

    // --- Search -------------------------------------------------------------

    public function test_the_list_can_be_searched_by_name(): void
    {
        $this->admin();
        TypeDepense::create(['nom' => 'Loyer', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);
        TypeDepense::create(['nom' => 'Électricité', 'is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF]);

        Livewire::test(TypesDepensesIndex::class)
            ->set('search', 'Loyer')
            ->assertSee('Loyer')
            ->assertDontSee('Électricité');
    }
}
