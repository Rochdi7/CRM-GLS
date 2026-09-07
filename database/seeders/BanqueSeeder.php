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

        // firstOrCreate, PAS updateOrCreate (07/09/2026) : `db:seed` est
        // rejouable sur la production, et réécrire `statut` y ressuscitait à
        // chaque déploiement une banque qu'un administrateur avait archivée
        // depuis Paramètres. Le seeder GARNIT le catalogue ; ce qui a été
        // fermé à la main le reste.
        foreach ($banques as $nom) {
            Banque::query()->firstOrCreate(
                ['nom' => $nom],
                ['statut' => Banque::STATUT_ACTIF],
            );
        }
    }
}
