<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Stock;

use App\Models\Etablissement;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use App\Models\StockType;
use Database\Seeders\BookStockSeeder;
use Database\Seeders\StockTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookStockSeeder is catalog data (the GLS book titles, one row per center)
 * and must never invent a quantity or a movement.
 */
final class BookStockSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_center_gets_every_title_at_zero_quantity(): void
    {
        $centres = Etablissement::factory()->count(3)->create();
        $this->seed(StockTypeSeeder::class);

        $this->seed(BookStockSeeder::class);

        $livreType = StockType::where('nom', StockType::SYSTEM_LIVRE)->firstOrFail();
        $expected = count(BookStockSeeder::TITLES) * $centres->count();

        $this->assertSame($expected, StockArticle::count());
        $this->assertSame(0, StockMouvement::count());

        foreach ($centres as $centre) {
            $rows = StockArticle::where('etablissement_id', $centre->id)->get();
            $this->assertCount(count(BookStockSeeder::TITLES), $rows);
            $this->assertEqualsCanonicalizing(BookStockSeeder::TITLES, $rows->pluck('nom')->all());
            $this->assertTrue($rows->every(fn (StockArticle $a) => $a->quantite === 0
                && $a->stock_type_id === $livreType->id
                && $a->statut === StockArticle::STATUT_ACTIF));
        }

        $this->assertSame(StockArticle::count(), StockArticle::distinct('reference')->count('reference'));
    }

    public function test_rerunning_never_duplicates_nor_touches_existing_quantities(): void
    {
        $centre = Etablissement::factory()->create();
        $this->seed(StockTypeSeeder::class);
        $this->seed(BookStockSeeder::class);

        $article = StockArticle::where('etablissement_id', $centre->id)->firstOrFail();
        $article->update(['quantite' => 25]); // real stock entered by the center

        $newCentre = Etablissement::factory()->create();
        $this->seed(BookStockSeeder::class);

        $this->assertSame(count(BookStockSeeder::TITLES) * 2, StockArticle::count());
        $this->assertSame(25, $article->fresh()->quantite);
        $this->assertSame(count(BookStockSeeder::TITLES), StockArticle::where('etablissement_id', $newCentre->id)->count());
    }
}
