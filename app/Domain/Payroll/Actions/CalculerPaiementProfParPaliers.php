<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Actions;

use App\Domain\Payroll\DTOs\LignePaiementProf;
use App\Domain\Payroll\DTOs\ResultatPaiementProf;

/**
 * Calcul « Paiement prof » (modes GLS / win-win) — PAR PALIERS DE PRÉSENCES
 * (règle du CEO, 30/09/2026).
 *
 *     présences du mois   rémunération
 *     < 5                 0 DH
 *     5 – 6               1 semaine  (taux ÷ 4)
 *     7 – 10              2 semaines (taux ÷ 2)
 *     11 et +             le mois complet (taux entier)
 *
 * ⚠ Ceci REMPLACE le calcul proportionnel du 22/09/2026
 * (`taux ÷ séances × présences`, plafonné à 22 séances), qui retirait de
 * l'argent pour CHAQUE absence : sur « Yassmina 10H A1 » (septembre 2026,
 * 20 séances, taux 400), un étudiant présent 18 fois ressortait à 360 DH,
 * 17 fois à 340 DH — et le CEO corrigeait ligne par ligne à 400 DH, ou à
 * 0 DH pour ceux qui étaient arrivés en fin de mois / partis au début.
 * Relevé en base, ses ajustements tombaient EXACTEMENT sur ces paliers
 * (17 et 15 présences → 400 ; 3 et 1 → 0).
 *
 * Le palier ne dépend PAS du nombre de séances du mois : un mois court ou
 * chargé ne change rien, seul compte ce que l'étudiant a suivi.
 *
 * ⚠ Cette action NE PERSISTE RIEN et ne touche AUCUNE caisse. Elle produit
 * un montant ; c'est l'utilisateur qui décide ensuite d'enregistrer la
 * dépense par le chemin ordinaire (`EnregistrerDepense`), avec tous ses
 * invariants monétaires (§11). Un calcul n'est pas un paiement.
 */
final class CalculerPaiementProfParPaliers
{
    /** Un mois compte 4 semaines : une semaine = taux ÷ 4. */
    public const SEMAINES_PAR_MOIS = 4;

    /**
     * Paliers, du plus haut au plus bas : présences MINIMALES => semaines
     * payées. En dessous du dernier palier, rien.
     *
     * @var array<int, int>
     */
    public const PALIERS = [
        11 => self::SEMAINES_PAR_MOIS, // complet
        7 => 2,
        5 => 1,
    ];

    /** Semaines payées pour un nombre de présences. */
    public static function semainesPour(int $presences): int
    {
        foreach (self::PALIERS as $minimum => $semaines) {
            if ($presences >= $minimum) {
                return $semaines;
            }
        }

        return 0;
    }

    /**
     * @param  array<int, array{student_id: int, nom: string, retenus: int, absents: int, ignores: int}>  $etudiants
     * @param  int  $nombreSeances  séances RÉELLEMENT effectuées sur la période (affichage seulement)
     * @param  array<int, float|null>  $ajustements  student_id => montant imposé
     */
    public function handle(
        array $etudiants,
        int $nombreSeances,
        float $montantParEtudiant,
        array $ajustements = [],
    ): ResultatPaiementProf {
        $lignes = [];
        $total = 0.0;
        $etudiantsRemunerateurs = 0;

        foreach ($etudiants as $etudiant) {
            $semaines = self::semainesPour(max($etudiant['retenus'], 0));

            // Calculé sur le taux ENTIER : « complet » vaut exactement le
            // taux, jamais 4 × une part arrondie.
            $auto = round($montantParEtudiant * $semaines / self::SEMAINES_PAR_MOIS, 2);

            // Un ajustement manuel REMPLACE le montant calculé. Il est saisi
            // à l'écran et ne survit pas au-delà du calcul affiché : rien
            // n'est stocké ici.
            $ajustement = $ajustements[$etudiant['student_id']] ?? null;
            $effectif = $ajustement !== null ? round($ajustement, 2) : $auto;

            if ($effectif > 0) {
                $etudiantsRemunerateurs++;
            }

            $lignes[] = new LignePaiementProf(
                studentId: $etudiant['student_id'],
                nom: $etudiant['nom'],
                joursRetenus: $etudiant['retenus'],
                joursAbsents: $etudiant['absents'],
                joursIgnores: $etudiant['ignores'],
                semainesPayees: $semaines,
                montantAuto: $auto,
                montantAjuste: $ajustement !== null ? round($ajustement, 2) : null,
                montantEffectif: $effectif,
            );

            $total += $effectif;
        }

        return new ResultatPaiementProf(
            lignes: $lignes,
            total: round($total, 2),
            montantParEtudiant: round($montantParEtudiant, 2),
            montantParSemaine: round($montantParEtudiant / self::SEMAINES_PAR_MOIS, 2),
            nombreSeances: $nombreSeances,
            etudiantsRemunerateurs: $etudiantsRemunerateurs,
        );
    }
}
