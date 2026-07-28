<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Frais;
use Illuminate\Database\Seeder;

/**
 * Starter fee catalog (idempotent) — the common GLS frais from the reference
 * screens. Admins manage the rest under Paramètres → Frais.
 */
final class FraisSeeder extends Seeder
{
    public function run(): void
    {
        $frais = [
            'Frais d\'inscription A1/A2/B1',
            'Frais d\'inscription B2',
            'Frais annuel',
            'Frais de Septembre',
            'Frais d\'Octobre',
            'Frais de Novembre',
            'Frais de Décembre',
            'Frais de Janvier',
            'Frais de Février',
            'Frais de Mars',
            'Frais d\'Avril',
            'Frais de Mai',
            'Frais de Juin',
            'Frais de Juillet',
            'Frais de Août',
            'Frais dexam ÖSD A1',
            'Frais dexam ÖSD B1',
            'Frais dexam ÖSD B2',
        ];

        foreach ($frais as $nom) {
            Frais::query()->updateOrCreate(
                ['nom' => $nom],
                ['statut' => Frais::STATUT_ACTIF],
            );
        }
    }
}
