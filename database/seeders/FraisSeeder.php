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
        // nom => montant par defaut. The amount is what a group starts
        // from when its own fee line is left blank - it stays editable per
        // group (group_frais.montant remains the authority).
        $frais = [
            "Frais d'inscription A1/A2/B1" => 300,
            "Frais d'inscription B2" => 300,
            'Frais annuel' => 0,
            'Frais de Septembre' => 1300,
            "Frais d'Octobre" => 1300,
            'Frais de Novembre' => 1300,
            'Frais de Décembre' => 1300,
            'Frais de Janvier' => 1300,
            'Frais de Février' => 1300,
            'Frais de Mars' => 1300,
            "Frais d'Avril" => 1300,
            'Frais de Mai' => 1300,
            'Frais de Juin' => 1300,
            'Frais de Juillet' => 1300,
            'Frais de Août' => 1300,
            'Frais dexam ÖSD A1' => 0,
            'Frais dexam ÖSD B1' => 0,
            'Frais dexam ÖSD B2' => 0,
        ];

        foreach ($frais as $nom => $montantDefaut) {
            $existing = Frais::query()->where('nom', $nom)->first();

            if ($existing === null) {
                Frais::query()->create([
                    'nom' => $nom,
                    'montant_defaut' => $montantDefaut,
                    'statut' => Frais::STATUT_ACTIF,
                ]);

                continue;
            }

            // Never reset an amount an admin has already tuned in
            // Paramètres — only fill one still sitting at zero.
            $existing->update([
                'statut' => Frais::STATUT_ACTIF,
                'montant_defaut' => (float) $existing->montant_defaut > 0
                    ? $existing->montant_defaut
                    : $montantDefaut,
            ]);
        }
    }
}
