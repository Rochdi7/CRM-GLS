<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Etablissement;
use App\Models\StockArticle;
use App\Models\StockMouvement;
use App\Models\StockType;
use Illuminate\Database\Seeder;

/**
 * Seeds the 8 GLS book titles as ONE StockArticle row PER center (same
 * title, different etablissement_id/quantite/reference — see the
 * stock_articles migration's docblock for why this is modeled as separate
 * rows rather than a single row with a per-center pivot). Each row starts
 * at 40 in stock, recorded as a genuine Entrée movement (same audit-trail
 * rule as every other stock quantity — never a raw insert). Idempotent:
 * skips a (title, center) pair that already has a StockArticle row.
 */
final class BookStockSeeder extends Seeder
{
    private const STARTING_QUANTITY = 40;

    private const TITLES = [
        'Daf Kompakt neu A1',
        'Daf Kompakt neu A2',
        'Daf Kompakt neu B1',
        'Aspekte neu B2 Lehrbuch',
        'Aspekte neu B2 Arbeitbuch',
        'Spektrum Deutsch A1+',
        'Spektrum Deutsch A2+',
        'Spektrum Deutsch B1+',
    ];

    public function run(): void
    {
        $livreType = StockType::where('nom', StockType::SYSTEM_LIVRE)->first();

        if ($livreType === null) {
            return;
        }

        $centres = Etablissement::query()->orderBy('id')->get(['id']);

        foreach ($centres as $centre) {
            foreach (self::TITLES as $titre) {
                $exists = StockArticle::query()
                    ->where('nom', $titre)
                    ->where('etablissement_id', $centre->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $article = StockArticle::create([
                    'reference' => ReferenceGenerator::make('ART', 'stock_articles'),
                    'nom' => $titre,
                    'stock_type_id' => $livreType->id,
                    'quantite' => 0, // rebuilt below through the Entrée movement
                    'seuil_alerte' => 10,
                    'etablissement_id' => $centre->id,
                    'statut' => StockArticle::STATUT_ACTIF,
                    'note' => null,
                ]);

                StockMouvement::create([
                    'stock_article_id' => $article->id,
                    'type' => StockMouvement::TYPE_ENTREE,
                    'quantite' => self::STARTING_QUANTITY,
                    'quantite_avant' => 0,
                    'quantite_apres' => self::STARTING_QUANTITY,
                    'note' => 'Stock initial',
                    'created_by' => null,
                ]);

                $article->update(['quantite' => self::STARTING_QUANTITY]);
            }
        }
    }
}
