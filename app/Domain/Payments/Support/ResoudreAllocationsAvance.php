<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Encaissement;

/**
 * Où est FINALEMENT allé l'argent d'une avance (02/09/2026).
 *
 * Une avance est dépensée par des lignes d'application
 * (`applied_from_encaissement_id`). Mais une application peut à son tour être
 * reconvertie en avance (`ConvertirEncaissementsEnAvance` détache son frais et
 * GARDE le lien vers le parent), puis ré-appliquée — ce qui crée une TROISIÈME
 * ligne, rattachée à la ligne détachée, pas à l'avance d'origine :
 *
 *     ENC-1586 (avance 300)
 *       └─ ENC-24329 (appliquée, puis reconvertie : frais NULL)
 *            └─ ENC-24369 (ré-appliquée : « Frais de Juillet »)
 *
 * Toute lecture qui ne suivait qu'UN niveau affichait alors pour ENC-1586
 * « Appliquée : 300 → Frais non lié : 300 » alors que l'argent est bien sur
 * un frais (signalé sur WIJDANE IDRISSI, ENC-1586). Cette classe est la SEULE
 * définition de la chaîne : la liste des encaissements, la page détail et le
 * libellé du reçu (`Encaissement::libelleFrais()`) la partagent, donc les trois
 * ne peuvent pas diverger.
 *
 * Pour chaque avance demandée, elle rend ses allocations TERMINALES :
 *   - `frais`     : une ligne attachée à un frais (l'argent est arrivé) ;
 *   - `rembourse` : une ligne détachée dont l'argent a été rendu à l'étudiant ;
 *   - `non_lie`   : le reste d'une ligne détachée encore disponible (elle est
 *                   listée dans l'onglet Avances, c'est là que l'argent attend).
 * La somme des montants d'une avance = son `montantUtilise()` moins ses propres
 * remboursements, donc « Appliquée : X » et son détail concordent toujours.
 *
 * UNE requête par niveau de profondeur pour toute la page (jamais une par
 * ligne — règle des read-models, CLAUDE.md §17), bornée pour qu'une donnée
 * corrompue en boucle ne bloque jamais un écran.
 */
final class ResoudreAllocationsAvance
{
    public const KIND_FRAIS = 'frais';

    public const KIND_REMBOURSE = 'rembourse';

    public const KIND_NON_LIE = 'non_lie';

    private const PROFONDEUR_MAX = 20;

    /**
     * Keyed by the requested avance id. `row` is the terminal line: the
     * fee-attached application, or the detached line whose money was
     * refunded / still waits.
     *
     * @param  list<int>  $avanceIds
     * @return array<int, list<array{kind: string, row: Encaissement, montant: float}>>
     */
    public static function terminales(array $avanceIds): array
    {
        if ($avanceIds === []) {
            return [];
        }

        $result = [];
        // Every visited application maps back to the ROOT avance it descends
        // from, whatever the depth.
        $racines = array_combine($avanceIds, $avanceIds);
        $frontiere = $avanceIds;

        for ($profondeur = 0; $profondeur < self::PROFONDEUR_MAX && $frontiere !== []; $profondeur++) {
            $applications = Encaissement::query()
                ->whereIn('applied_from_encaissement_id', $frontiere)
                ->with(['fee.inscription.group'])
                ->withSum('applications', 'montant')
                ->withSum('remboursements', 'montant')
                ->orderBy('date_paiement')
                ->orderBy('id')
                ->get();

            $frontiere = [];

            foreach ($applications as $application) {
                $racine = $racines[(int) $application->applied_from_encaissement_id];
                $montant = round((float) $application->montant, 2);

                if ($application->inscription_fee_id !== null) {
                    $result[$racine][] = ['kind' => self::KIND_FRAIS, 'row' => $application, 'montant' => $montant];

                    continue;
                }

                // Detached (reconverted) application: follow its own money.
                $reapplique = round((float) ($application->applications_sum_montant ?? 0), 2);
                $rembourse = round((float) ($application->remboursements_sum_montant ?? 0), 2);
                $reste = round($montant - $reapplique - $rembourse, 2);

                if ($rembourse > 0) {
                    $result[$racine][] = ['kind' => self::KIND_REMBOURSE, 'row' => $application, 'montant' => $rembourse];
                }

                if ($reste > 0) {
                    $result[$racine][] = ['kind' => self::KIND_NON_LIE, 'row' => $application, 'montant' => $reste];
                }

                if ($reapplique > 0) {
                    $racines[(int) $application->id] = $racine;
                    $frontiere[] = (int) $application->id;
                }
            }
        }

        return $result;
    }

    /**
     * Human label for one terminal allocation: the fee's name, or what became
     * of money that reached no fee.
     */
    public static function libelle(array $allocation): string
    {
        return match ($allocation['kind']) {
            self::KIND_FRAIS => (string) $allocation['row']->fee?->nom,
            self::KIND_REMBOURSE => __('Refunded'),
            default => __('Unlinked fee'),
        };
    }
}
