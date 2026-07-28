<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TypeDepense;
use Illuminate\Database\Seeder;

/**
 * Seeds the fixed SYSTEM expense types (schema §12) — locked from admin
 * editing because payroll automation and till transfers depend on their
 * exact names existing. The admin form only ever creates is_system = false.
 */
final class TypeDepenseSeeder extends Seeder
{
    public function run(): void
    {
        $systemTypes = [
            TypeDepense::SYSTEM_PAIEMENT_PROF,
            TypeDepense::SYSTEM_SALAIRE,
            TypeDepense::SYSTEM_TRANSFERT_CAISSE,
        ];

        foreach ($systemTypes as $nom) {
            TypeDepense::query()->firstOrCreate(
                ['nom' => $nom],
                ['is_system' => true, 'statut' => TypeDepense::STATUT_ACTIF],
            );
        }

        // Starter catalog from the legacy app — ordinary types the admin can
        // rename/deactivate freely (is_system = false, unlike the ones above).
        $starterTypes = [
            'Syndic',
            'Eau et Electricité',
            'Internet et télécommunication',
            'Impôts et taxes',
            'Logistiques',
            'Externalisation ou sous-traitance',
            'Femme de ménage',
        ];

        foreach ($starterTypes as $nom) {
            TypeDepense::query()->firstOrCreate(
                ['nom' => $nom],
                ['is_system' => false, 'statut' => TypeDepense::STATUT_ACTIF],
            );
        }
    }
}
