<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Etablissement;
use App\Models\StockArticle;
use App\Models\StockType;
use Illuminate\Database\Seeder;

/**
 * Seeds the GLS book catalog (the 8 titles sold with an inscription) as ONE
 * StockArticle row PER center — same title, different etablissement_id /
 * reference — so every center manages its own quantity of every title (see
 * the stock_articles migration docblock for why per-center stock is
 * separate rows, not a pivot).
 *
 * This is reference data, NOT demo data: every row is created with
 * `quantite` = 0 and no movement. The real quantity of each center is
 * entered through an « Entrée » movement in Gestion du stock (or the Import
 * screen), which is the only way a stock quantity may ever change. The
 * previous version of this seeder invented 40 units per row — that is why
 * it was deleted with the Demo* family; this one never invents a quantity.
 *
 * Idempotent: a (title, center) pair that already has a row is skipped, so
 * re-running on a live database adds the titles only to centers created
 * since the last run and never touches an existing quantity.
 */
final class BookStockSeeder extends Seeder
{
    public const ALERT_THRESHOLD = 10;

    /** @var list<string> */
    public const TITLES = [
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
        $livreType = StockType::query()->where('nom', StockType::SYSTEM_LIVRE)->first();

        if ($livreType === null) {
            $this->command?->warn('BookStockSeeder: StockType « Livre » missing — run StockTypeSeeder first.');

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

                StockArticle::create([
                    'reference' => ReferenceGenerator::make('ART', 'stock_articles'),
                    'nom' => $titre,
                    'stock_type_id' => $livreType->id,
                    'quantite' => 0, // real stock arrives through an Entrée movement
                    'seuil_alerte' => self::ALERT_THRESHOLD,
                    'etablissement_id' => $centre->id,
                    'statut' => StockArticle::STATUT_ACTIF,
                    'note' => null,
                ]);
            }
        }
    }
}
