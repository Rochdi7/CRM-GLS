<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StockType;
use Illuminate\Database\Seeder;

/**
 * Seeds the 6 original stock categories as system StockType rows (locked
 * from admin editing/deletion), same pattern as TypeDepenseSeeder. "Livre"
 * replaces the old "Livres et manuels" CATEGORIES value.
 */
final class StockTypeSeeder extends Seeder
{
    public function run(): void
    {
        $systemTypes = [
            'Fournitures de bureau',
            'Matériel pédagogique',
            StockType::SYSTEM_LIVRE,
            'Consommables',
            'Équipement',
            'Autre',
        ];

        foreach ($systemTypes as $nom) {
            StockType::query()->firstOrCreate(
                ['nom' => $nom],
                ['is_system' => true, 'statut' => StockType::STATUT_ACTIF],
            );
        }
    }
}
