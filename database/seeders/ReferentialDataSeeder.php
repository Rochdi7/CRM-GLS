<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Etablissement;
use App\Models\Salle;
use Illuminate\Database\Seeder;

/**
 * Seeds GLS reference data (idempotent — safe to re-run):
 *  - academic years 2024/2025 and 2025/2026 (2025/2026 = default)
 *  - the 6 GLS branches (Marrakech is head office)
 *  - 7 rooms per branch (3 teacher-named + 4 German city names)
 *
 * Run alone:  php artisan db:seed --class=ReferentialDataSeeder
 */
final class ReferentialDataSeeder extends Seeder
{
    /**
     * Room name => capacity, seeded for every branch.
     *
     * Two naming styles coexist on purpose (both appear in production):
     * teacher-named rooms ("SALLE 01 ALAOUI") and German city names
     * (German language school).
     */
    private const SALLES_PAR_CENTRE = [
        'SALLE 01 ALAOUI' => 26,
        'SALLE 02 ADIL' => 22,
        'SALLE 03 ABDELLAH' => 18,
        'Berlin' => 30,
        'München' => 24,
        'Hamburg' => 20,
        'Köln' => 16,
    ];

    /** Legacy room name => new name, migrated in place (keeps salle_id). */
    private const RENOMMAGES = [
        'Salle 01' => 'Berlin',
        'Salle 02' => 'München',
    ];

    public function run(): void
    {
        $this->seedAnneesScolaires();
        $this->seedEtablissementsEtSalles();
    }

    private function seedAnneesScolaires(): void
    {
        AnneeScolaire::query()->updateOrCreate(
            ['nom' => '2024/2025'],
            [
                'date_debut' => '2024-09-01',
                'date_fin' => '2025-08-31',
                'par_defaut' => false,
                'inscription_ouverte' => false,
            ],
        );

        AnneeScolaire::query()->updateOrCreate(
            ['nom' => '2025/2026'],
            [
                'date_debut' => '2025-09-01',
                'date_fin' => '2026-08-31',
                'par_defaut' => true,
                'inscription_ouverte' => true,
            ],
        );

        // Guarantee exactly one default year.
        AnneeScolaire::query()->where('nom', '!=', '2025/2026')->update(['par_defaut' => false]);
    }

    private function seedEtablissementsEtSalles(): void
    {
        // [ville, siège social ?]
        $centres = [
            'GLS Marrakech' => ['Marrakech', true],
            'GLS Rabat' => ['Rabat', false],
            'GLS Casablanca' => ['Casablanca', false],
            'GLS Kénitra' => ['Kénitra', false],
            'GLS Agadir' => ['Agadir', false],
            'GLS Salé' => ['Salé', false],
            'GLS Online' => ['En ligne', false],
        ];

        foreach ($centres as $nom => [$ville, $siege]) {
            $etab = Etablissement::query()->updateOrCreate(
                ['nom_centre' => $nom],
                ['ville' => $ville, 'siege_social' => $siege],
            );

            $this->renommerAnciennesSalles($etab->id);

            // Rooms are named after German cities (German language school),
            // 4 per branch — created once, capacity kept stable on re-run.
            foreach (self::SALLES_PAR_CENTRE as $salleNom => $capacite) {
                Salle::query()->updateOrCreate(
                    ['etablissement_id' => $etab->id, 'nom' => $salleNom],
                    ['capacite' => $capacite, 'statut' => Salle::STATUT_ACTIVE],
                );
            }
        }
    }

    /**
     * Renames the rooms seeded under the previous scheme ("Salle 01"/"Salle 02")
     * to their German-city equivalent, in place — so groups already pointing at
     * those rooms keep their salle_id instead of being orphaned by a delete.
     * Skips a rename when the target name already exists in that branch.
     */
    private function renommerAnciennesSalles(int $etablissementId): void
    {
        foreach (self::RENOMMAGES as $ancienNom => $nouveauNom) {
            $ancienne = Salle::query()
                ->where('etablissement_id', $etablissementId)
                ->where('nom', $ancienNom)
                ->first();

            if ($ancienne === null) {
                continue;
            }

            $conflit = Salle::query()
                ->where('etablissement_id', $etablissementId)
                ->where('nom', $nouveauNom)
                ->whereKeyNot($ancienne->getKey())
                ->exists();

            if ($conflit) {
                continue;
            }

            $ancienne->update([
                'nom' => $nouveauNom,
                'capacite' => self::SALLES_PAR_CENTRE[$nouveauNom],
            ]);
        }
    }
}
