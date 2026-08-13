<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Stock;

use App\Models\Role;
use App\Models\StockArticle;
use App\Models\StockType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StockTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Types de stock CRUD — replaces StockArticle's old hardcoded CATEGORIES
 * array. Mirrors TypesDepensesCrudTest one-for-one (its direct model
 * template, TypeDepense).
 */
final class StockTypesInertiaCrudTest extends TestCase
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
        $this->withPermissions('stock-types.view');
        StockType::create(['nom' => 'Papeterie', 'is_system' => false, 'statut' => StockType::STATUT_ACTIF]);

        $this->get(route('backoffice.stock-types.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/StockTypes/Index')
                ->where('types.data.0.nom', 'Papeterie')
            );
    }

    public function test_the_page_is_forbidden_without_the_view_permission(): void
    {
        $this->withPermissions('dashboard.view');

        $this->get(route('backoffice.stock-types.index'))->assertForbidden();
    }

    public function test_creating_is_forbidden_without_the_create_permission(): void
    {
        $this->withPermissions('stock-types.view');

        $this->post(route('backoffice.stock-types.store'), ['nom' => 'X', 'statut' => StockType::STATUT_ACTIF])
            ->assertForbidden();
    }

    // --- Create / update ------------------------------------------------------

    public function test_a_custom_stock_type_can_be_created(): void
    {
        $this->withPermissions('stock-types.view', 'stock-types.create');

        $this->post(route('backoffice.stock-types.store'), [
            'nom' => 'Vêtements', 'statut' => StockType::STATUT_ACTIF,
        ])->assertRedirect(route('backoffice.stock-types.index'));

        // The form NEVER creates a system type.
        $this->assertDatabaseHas('stock_types', [
            'nom' => 'Vêtements',
            'statut' => StockType::STATUT_ACTIF,
            'is_system' => false,
        ]);
    }

    public function test_the_name_is_required_and_must_be_unique(): void
    {
        $this->admin();
        StockType::create(['nom' => 'Papeterie', 'is_system' => false, 'statut' => StockType::STATUT_ACTIF]);

        $this->post(route('backoffice.stock-types.store'), ['nom' => '', 'statut' => StockType::STATUT_ACTIF])
            ->assertSessionHasErrors('nom');

        $this->post(route('backoffice.stock-types.store'), ['nom' => 'Papeterie', 'statut' => StockType::STATUT_ACTIF])
            ->assertSessionHasErrors('nom');
    }

    public function test_the_status_must_be_a_known_value(): void
    {
        $this->admin();

        $this->post(route('backoffice.stock-types.store'), ['nom' => 'Divers', 'statut' => 'Nimporte'])
            ->assertSessionHasErrors('statut');
    }

    public function test_a_custom_stock_type_can_be_updated(): void
    {
        $this->withPermissions('stock-types.view', 'stock-types.update');
        $type = StockType::create(['nom' => 'Papeterie', 'is_system' => false, 'statut' => StockType::STATUT_ACTIF]);

        $this->put(route('backoffice.stock-types.update', $type), [
            'nom' => 'Papeterie & fournitures', 'statut' => StockType::STATUT_INACTIF,
        ])->assertRedirect(route('backoffice.stock-types.index'));

        $this->assertDatabaseHas('stock_types', [
            'id' => $type->id,
            'nom' => 'Papeterie & fournitures',
            'statut' => StockType::STATUT_INACTIF,
        ]);
    }

    // --- System types are LOCKED ----------------------------------------------

    public function test_a_system_stock_type_cannot_be_edited_even_by_a_super_admin(): void
    {
        $this->admin();
        $this->seed(StockTypeSeeder::class);
        $system = StockType::where('nom', StockType::SYSTEM_LIVRE)->firstOrFail();

        $this->put(route('backoffice.stock-types.update', $system), ['nom' => 'Piraté', 'statut' => StockType::STATUT_ACTIF])
            ->assertForbidden();

        $this->assertDatabaseHas('stock_types', ['id' => $system->id, 'nom' => StockType::SYSTEM_LIVRE]);
    }

    public function test_a_system_stock_type_cannot_be_deleted(): void
    {
        $this->admin();
        $this->seed(StockTypeSeeder::class);
        $system = StockType::where('nom', StockType::SYSTEM_LIVRE)->firstOrFail();

        $this->delete(route('backoffice.stock-types.destroy', $system))->assertForbidden();

        $this->assertDatabaseHas('stock_types', ['id' => $system->id]);
    }

    public function test_system_rows_are_marked_in_the_props(): void
    {
        $this->admin();
        $this->seed(StockTypeSeeder::class);

        $this->get(route('backoffice.stock-types.index'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'types.data',
                fn ($rows) => collect($rows)->contains(fn ($row) => $row['nom'] === StockType::SYSTEM_LIVRE && $row['isSystem'] === true),
            ));
    }

    public function test_is_system_cannot_be_forged_from_client_input_on_create(): void
    {
        $this->withPermissions('stock-types.view', 'stock-types.create');

        $this->post(route('backoffice.stock-types.store'), [
            'nom' => 'Faux système', 'statut' => StockType::STATUT_ACTIF, 'is_system' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_types', ['nom' => 'Faux système', 'is_system' => false]);
    }

    // --- Delete ---------------------------------------------------------------

    public function test_deleting_is_blocked_when_the_type_is_used_by_articles(): void
    {
        $this->admin();
        $type = StockType::create(['nom' => 'Papeterie', 'is_system' => false, 'statut' => StockType::STATUT_ACTIF]);

        StockArticle::create([
            'reference' => 'ART-000001',
            'nom' => 'Ramette A4',
            'stock_type_id' => $type->id,
            'quantite' => 0,
            'statut' => StockArticle::STATUT_ACTIF,
        ]);

        $this->delete(route('backoffice.stock-types.destroy', $type))->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('stock_types', ['id' => $type->id]);
    }

    public function test_an_unused_custom_stock_type_can_be_deleted(): void
    {
        $this->withPermissions('stock-types.view', 'stock-types.delete');
        $type = StockType::create(['nom' => 'Obsolète', 'is_system' => false, 'statut' => StockType::STATUT_ACTIF]);

        $this->delete(route('backoffice.stock-types.destroy', $type))->assertRedirect();

        $this->assertDatabaseMissing('stock_types', ['id' => $type->id]);
    }

    public function test_deleting_is_forbidden_without_the_delete_permission(): void
    {
        $this->withPermissions('stock-types.view');
        $type = StockType::create(['nom' => 'Obsolète', 'is_system' => false, 'statut' => StockType::STATUT_ACTIF]);

        $this->delete(route('backoffice.stock-types.destroy', $type))->assertForbidden();

        $this->assertDatabaseHas('stock_types', ['id' => $type->id]);
    }

    // --- Search -----------------------------------------------------------

    public function test_the_list_can_be_searched_by_name(): void
    {
        $this->admin();
        StockType::create(['nom' => 'Papeterie', 'is_system' => false, 'statut' => StockType::STATUT_ACTIF]);
        StockType::create(['nom' => 'Électronique', 'is_system' => false, 'statut' => StockType::STATUT_ACTIF]);

        $this->get(route('backoffice.stock-types.index', ['search' => 'Papeterie']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('types.data.0.nom', 'Papeterie')
                ->where('types.total', 1)
            );
    }
}
