<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Stock;

use App\Models\Etablissement;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use App\Models\StockType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Stock module — articles CRUD + append-only movements
 * (StockController / EnregistrerMouvementStock).
 */
final class StockInertiaCrudTest extends TestCase
{
    use RefreshDatabase;

    private Etablissement $centre;

    private StockType $livreType;

    private StockType $fournituresType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->centre = Etablissement::factory()->create();
        $this->livreType = StockType::create(['nom' => StockType::SYSTEM_LIVRE, 'is_system' => true, 'statut' => StockType::STATUT_ACTIF]);
        $this->fournituresType = StockType::create(['nom' => 'Fournitures de bureau', 'is_system' => true, 'statut' => StockType::STATUT_ACTIF]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }

        return $user->fresh();
    }

    private function makeArticle(array $attributes = []): StockArticle
    {
        return StockArticle::create(array_merge([
            'reference' => 'ART-'.str_pad((string) (StockArticle::count() + 1), 6, '0', STR_PAD_LEFT),
            'nom' => 'Manuel A1',
            'stock_type_id' => $this->livreType->id,
            'quantite' => 0,
            'statut' => StockArticle::STATUT_ACTIF,
        ], $attributes));
    }

    public function test_index_requires_stock_view_and_renders_the_react_page(): void
    {
        $this->actingAs($this->userWith('dashboard.view'))
            ->get(route('backoffice.stock.index'))
            ->assertForbidden();

        $this->makeArticle();

        $this->actingAs($this->userWith('stock.view'))
            ->get(route('backoffice.stock.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/Stock/Index', false)
                ->has('articles.data', 1)
                ->has('mouvements.data', 0)
                ->has('stockTypes')
                ->has('mouvementTypes')
                ->where('permissions.create', false)
            );
    }

    public function test_an_article_is_created_with_a_generated_reference_and_zero_quantity(): void
    {
        $this->actingAs($this->userWith('stock.view', 'stock.create'));

        $this->post(route('backoffice.stock-articles.store'), [
            'nom' => 'Marqueurs effaçables',
            'stock_type_id' => $this->fournituresType->id,
            'seuil_alerte' => 10,
            'statut' => StockArticle::STATUT_ACTIF,
        ])->assertRedirect(route('backoffice.stock.index'));

        $article = StockArticle::firstOrFail();
        $this->assertMatchesRegularExpression('/^ART-\d{3,}$/', $article->reference);
        $this->assertSame(0, $article->quantite);
        $this->assertSame(10, $article->seuil_alerte);
    }

    public function test_store_requires_stock_create(): void
    {
        $this->actingAs($this->userWith('stock.view'))
            ->post(route('backoffice.stock-articles.store'), [
                'nom' => 'X',
                'stock_type_id' => $this->fournituresType->id,
                'statut' => StockArticle::STATUT_ACTIF,
            ])->assertForbidden();
    }

    public function test_updating_an_article_never_touches_its_quantity(): void
    {
        $article = $this->makeArticle(['quantite' => 7]);

        $this->actingAs($this->userWith('stock.view', 'stock.update'));

        $this->put(route('backoffice.stock-articles.update', $article), [
            'nom' => 'Manuel A1 (2e édition)',
            'stock_type_id' => $this->livreType->id,
            'quantite' => 999, // must be ignored — quantities move via movements only
            'statut' => StockArticle::STATUT_INACTIF,
        ])->assertRedirect();

        $article->refresh();
        $this->assertSame('Manuel A1 (2e édition)', $article->nom);
        $this->assertSame(StockArticle::STATUT_INACTIF, $article->statut);
        $this->assertSame(7, $article->quantite);
    }

    public function test_movements_adjust_the_quantity_and_keep_an_audit_trail(): void
    {
        $article = $this->makeArticle();

        $this->actingAs($this->userWith('stock.view', 'stock.move'));

        $this->post(route('backoffice.stock-mouvements.store'), [
            'stock_article_id' => $article->id,
            'type' => StockMouvement::TYPE_ENTREE,
            'quantite' => 20,
        ])->assertRedirect();

        $this->post(route('backoffice.stock-mouvements.store'), [
            'stock_article_id' => $article->id,
            'type' => StockMouvement::TYPE_SORTIE,
            'quantite' => 5,
            'note' => 'Distribution classe B1',
        ])->assertRedirect();

        $this->post(route('backoffice.stock-mouvements.store'), [
            'stock_article_id' => $article->id,
            'type' => StockMouvement::TYPE_AJUSTEMENT,
            'quantite' => 12,
            'note' => 'Inventaire',
        ])->assertRedirect();

        $this->assertSame(12, $article->fresh()->quantite);
        $this->assertSame(3, StockMouvement::count());
        $this->assertDatabaseHas('stock_mouvements', [
            'stock_article_id' => $article->id,
            'type' => StockMouvement::TYPE_SORTIE,
            'quantite_avant' => 20,
            'quantite_apres' => 15,
        ]);
    }

    public function test_a_sortie_that_would_go_negative_is_refused(): void
    {
        $article = $this->makeArticle(['quantite' => 3]);

        $this->actingAs($this->userWith('stock.view', 'stock.move'))
            ->from(route('backoffice.stock.index'))
            ->post(route('backoffice.stock-mouvements.store'), [
                'stock_article_id' => $article->id,
                'type' => StockMouvement::TYPE_SORTIE,
                'quantite' => 5,
            ])->assertSessionHasErrors(['quantite']);

        $this->assertSame(3, $article->fresh()->quantite);
        $this->assertSame(0, StockMouvement::count());
    }

    public function test_movements_require_stock_move(): void
    {
        $article = $this->makeArticle();

        $this->actingAs($this->userWith('stock.view'))
            ->post(route('backoffice.stock-mouvements.store'), [
                'stock_article_id' => $article->id,
                'type' => StockMouvement::TYPE_ENTREE,
                'quantite' => 1,
            ])->assertForbidden();
    }

    public function test_an_article_with_movements_cannot_be_deleted(): void
    {
        $article = $this->makeArticle();

        $this->actingAs($this->userWith('stock.view', 'stock.move', 'stock.delete'));

        $this->post(route('backoffice.stock-mouvements.store'), [
            'stock_article_id' => $article->id,
            'type' => StockMouvement::TYPE_ENTREE,
            'quantite' => 1,
        ]);

        $this->from(route('backoffice.stock.index'))
            ->delete(route('backoffice.stock-articles.destroy', $article))
            ->assertSessionHasErrors(['delete']);
        $this->assertDatabaseHas('stock_articles', ['id' => $article->id]);
    }

    public function test_an_article_without_movements_can_be_deleted(): void
    {
        $article = $this->makeArticle();

        $this->actingAs($this->userWith('stock.view', 'stock.delete'))
            ->delete(route('backoffice.stock-articles.destroy', $article))
            ->assertRedirect(route('backoffice.stock.index'));

        $this->assertDatabaseMissing('stock_articles', ['id' => $article->id]);
    }

    public function test_center_scoped_users_cannot_move_another_centers_stock(): void
    {
        $otherCentre = Etablissement::factory()->create();
        $article = $this->makeArticle(['etablissement_id' => $otherCentre->id]);

        // stock.move but NO centers.access-all and no employee profile —
        // confined to global (NULL-center) records.
        $user = User::factory()->create();
        $user->givePermissionTo('stock.view');
        $user->givePermissionTo('stock.move');

        $this->actingAs($user->fresh())
            ->post(route('backoffice.stock-mouvements.store'), [
                'stock_article_id' => $article->id,
                'type' => StockMouvement::TYPE_ENTREE,
                'quantite' => 1,
            ])->assertForbidden();
    }
}
