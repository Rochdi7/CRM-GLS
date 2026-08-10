<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Banque;
use Illuminate\Database\Seeder;

/**
 * Starter bank catalog (idempotent) — common Moroccan banks. Super-admins
 * manage the rest under Paramètres → Banques.
 */
final class BanqueSeeder extends Seeder
{
    public function run(): void
    {
        $banques = [
            'Attijariwafa Bank',
            'Banque Populaire',
            'BMCE Bank',
            'BMCI',
            'Société Générale Maroc',
            'Crédit Agricole du Maroc',
            'CIH Bank',
            'Al Barid Bank',
        ];

        foreach ($banques as $nom) {
            Banque::query()->updateOrCreate(
                ['nom' => $nom],
                ['statut' => Banque::STATUT_ACTIF],
            );
        }
    }
}
