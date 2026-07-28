<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use Illuminate\Database\Seeder;

/**
 * Seeds the current academic year as par_defaut = true (structure doc §9).
 */
final class AnneeScolaireSeeder extends Seeder
{
    public function run(): void
    {
        AnneeScolaire::query()->firstOrCreate(
            ['nom' => '2025/2026'],
            [
                'date_debut' => '2025-09-01',
                'date_fin' => '2026-08-31',
                'par_defaut' => true,
                'inscription_ouverte' => true,
            ],
        );
    }
}
