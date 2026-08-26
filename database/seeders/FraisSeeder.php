<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Etablissement;
use App\Models\Frais;
use Illuminate\Database\Seeder;

/**
 * Starter fee catalog (idempotent) — the common GLS frais from the
 * reference screens, each PRICED PER CENTER.
 *
 * A fee is one catalog entry attached to the centers that charge it (the
 * frais_etablissement pivot), never duplicated per branch: duplicating
 * would fork one "Frais de Septembre" into seven rows and split every
 * group_frais / inscription_fees link across them. Only the AMOUNT varies
 * per center, so that is what lives on the pivot.
 *
 * Amounts are the rates published on the public tariff pages
 * (gls-sprachzentrum.ma, one tab per center). The monthly rate is flat
 * within a center — it does NOT vary by level, only the course DURATION
 * does (A1 2 mois, A2/B1 2,5 mois, B2 3 mois), which is a group concern,
 * not a fee one.
 *
 * Admins manage the rest under Paramètres → Frais.
 */
final class FraisSeeder extends Seeder
{
    /**
     * Monthly tuition by branch — the "Tarif mensuel" column of each
     * center's public tariff page. Online is the cheapest at 1000.
     */
    private const MENSUEL_PAR_CENTRE = [
        'GLS Casablanca' => 1400,
        'GLS Rabat' => 1400,
        'GLS Marrakech' => 1300,
        'GLS Kénitra' => 1200,
        'GLS Salé' => 1300,
        'GLS Agadir' => 1200,
        'GLS Online' => 1000,
    ];

    public function run(): void
    {
        // nom => [montant, mensuel ?]. `mensuel` fees are re-priced per
        // branch from MENSUEL_PAR_CENTRE; the others are the same amount
        // everywhere (published identically on every center's page) and so
        // are attached to all centers at their catalog amount.
        //
        // The amount is the fallback for a center with no explicit price
        // line, and stays editable per group (group_frais.montant remains
        // the authority).
        $frais = [
            // One-time, charged on first inscription — 300 DH nationwide.
            "Frais d'inscription A1/A2/B1" => [300, false],
            // The B2 inscription is its OWN one-time fee at 200 DH, not a
            // second 300: "Inscription niveau B2 : 200 DH (payée une seule
            // fois)" on every center's page.
            "Frais d'inscription B2" => [200, false],

            // Monthly tuition — the per-center rate.
            'Frais de Septembre' => [1300, true],
            "Frais d'Octobre" => [1300, true],
            'Frais de Novembre' => [1300, true],
            'Frais de Décembre' => [1300, true],
            'Frais de Janvier' => [1300, true],
            'Frais de Février' => [1300, true],
            'Frais de Mars' => [1300, true],
            "Frais d'Avril" => [1300, true],
            'Frais de Mai' => [1300, true],
            'Frais de Juin' => [1300, true],
            'Frais de Juillet' => [1300, true],
            'Frais de Août' => [1300, true],

            // ÖSD exam fees — national, identical at every center (the
            // public site lists them on a single "Examens" tab, not per
            // branch).
            'Frais dexam ÖSD A1' => [2000, false],
            'Frais dexam ÖSD B1' => [2300, false],
            'Frais dexam ÖSD B2' => [2500, false],
        ];

        $centres = Etablissement::query()->pluck('id', 'nom_centre');

        foreach ($frais as $nom => [$montantDefaut, $mensuel]) {
            $existing = Frais::query()->where('nom', $nom)->first();

            if ($existing === null) {
                $existing = Frais::query()->create([
                    'nom' => $nom,
                    'montant_defaut' => $montantDefaut,
                    'statut' => Frais::STATUT_ACTIF,
                ]);
            } else {
                // Never reset an amount an admin has already tuned in
                // Paramètres — only fill one still sitting at zero.
                $existing->update([
                    'statut' => Frais::STATUT_ACTIF,
                    'montant_defaut' => (float) $existing->montant_defaut > 0
                        ? $existing->montant_defaut
                        : $montantDefaut,
                ]);
            }

            $this->attacherCentres($existing, $centres, $montantDefaut, $mensuel);
        }
    }

    /**
     * Attach the fee to every center, at that center's price.
     *
     * Uses attach-if-missing rather than sync: an admin may have already
     * detached a fee from a branch that does not charge it, or corrected
     * its price, and re-seeding must not undo either.
     *
     * @param  \Illuminate\Support\Collection<string, int>  $centres
     */
    private function attacherCentres(Frais $frais, $centres, float $montantDefaut, bool $mensuel): void
    {
        $dejaAttaches = $frais->etablissements()->pluck('etablissements.id')->all();

        foreach ($centres as $nomCentre => $etablissementId) {
            if (in_array($etablissementId, $dejaAttaches, true)) {
                continue;
            }

            $montant = $mensuel
                ? (self::MENSUEL_PAR_CENTRE[$nomCentre] ?? $montantDefaut)
                : $montantDefaut;

            $frais->etablissements()->attach($etablissementId, ['montant' => $montant]);
        }
    }
}
