<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Actions;

use App\Domain\Payroll\DTOs\LignePaiementProf;
use App\Domain\Payroll\DTOs\ResultatPaiementProf;
use App\Domain\Payroll\Support\DecoupageSemaines;

/**
 * Calcul « Paiement prof » — mode HEBDOMADAIRE.
 *
 * Porté depuis le portail GLS (`ProfPaymentCalculationService`), à une
 * différence près : le portail lisait un instantané Excel importé, alors que
 * nous possédons les appels en base (`presences` → `seances`). Le calcul, lui,
 * est identique.
 *
 * La règle, par étudiant et par semaine de paie (4 buckets, voir
 * `DecoupageSemaines`) :
 *
 *     jours retenus >= seuil (3 par défaut)  ⇒  la semaine rapporte
 *                                               montant_par_etudiant × 25 %
 *     sinon                                  ⇒  0 DH
 *
 * Un étudiant présent les 4 semaines rapporte donc exactement le montant par
 * étudiant (4 × 25 %). Le total du professeur est la somme de toutes les
 * semaines qualifiées de tous ses étudiants.
 *
 * ⚠ Cette action NE PERSISTE RIEN et ne touche AUCUNE caisse. Elle produit un
 * montant ; c'est l'utilisateur qui décide ensuite d'enregistrer la dépense,
 * par le chemin ordinaire (`EnregistrerDepense`), avec tous ses invariants
 * monétaires (§11). Un calcul n'est pas un paiement.
 */
final class CalculerPaiementProfHebdomadaire
{
    /** Seuil de jours retenus pour qu'une semaine qualifie. */
    public const SEUIL_PAR_DEFAUT = 3;

    /** Part du montant par étudiant que rapporte UNE semaine qualifiée. */
    public const POURCENTAGE_HEBDO_PAR_DEFAUT = 25.0;

    /**
     * @param  array<int, array{student_id: int, nom: string, semaines: array<string, int>, retenus: int, absents: int, ignores: int}>  $etudiants
     *         Un enregistrement par étudiant, `semaines` étant le nombre de
     *         jours RETENUS par semaine ISO (« GGGG-WW »).
     * @param  list<string>  $datesDeCours  dates ayant eu au moins un appel
     * @param  array<int, float|null>  $ajustements  student_id => montant imposé
     */
    public function handle(
        array $etudiants,
        array $datesDeCours,
        float $montantParEtudiant,
        int $seuil = self::SEUIL_PAR_DEFAUT,
        float $pourcentageHebdo = self::POURCENTAGE_HEBDO_PAR_DEFAUT,
        array $ajustements = [],
    ): ResultatPaiementProf {
        $montantSemaine = round($montantParEtudiant * $pourcentageHebdo / 100, 2);
        $map = DecoupageSemaines::construire($datesDeCours, $seuil);

        $lignes = [];
        $total = 0.0;
        $semainesQualifiees = 0;
        $etudiantsRemunerateurs = 0;

        foreach ($etudiants as $etudiant) {
            $comptes = DecoupageSemaines::compter($map, $etudiant['semaines']);

            $montantsAuto = [];
            $totalAuto = 0.0;
            $qualifieAuMoinsUneSemaine = false;

            foreach ($comptes as $semaine => $jours) {
                $qualifie = $jours >= $seuil;
                $montantsAuto[$semaine] = $qualifie ? $montantSemaine : 0.0;

                if ($qualifie) {
                    $totalAuto += $montantSemaine;
                    $semainesQualifiees++;
                    $qualifieAuMoinsUneSemaine = true;
                }
            }

            // Un ajustement manuel REMPLACE le montant calculé de l'étudiant.
            // Il est saisi à l'écran, motivé, et ne survit pas au-delà du
            // calcul affiché : rien n'est stocké ici.
            $ajustement = $ajustements[$etudiant['student_id']] ?? null;
            $effectif = $ajustement !== null ? round($ajustement, 2) : round($totalAuto, 2);

            if ($effectif > 0) {
                $etudiantsRemunerateurs++;
            }

            $lignes[] = new LignePaiementProf(
                studentId: $etudiant['student_id'],
                nom: $etudiant['nom'],
                joursRetenus: $etudiant['retenus'],
                joursAbsents: $etudiant['absents'],
                joursIgnores: $etudiant['ignores'],
                joursParSemaine: $comptes,
                montantsParSemaine: $montantsAuto,
                montantAuto: round($totalAuto, 2),
                montantAjuste: $ajustement !== null ? round($ajustement, 2) : null,
                montantEffectif: $effectif,
                qualifie: $qualifieAuMoinsUneSemaine,
            );

            $total += $effectif;
        }

        return new ResultatPaiementProf(
            lignes: $lignes,
            total: round($total, 2),
            montantParEtudiant: round($montantParEtudiant, 2),
            montantSemaine: $montantSemaine,
            seuil: $seuil,
            nombreJoursDeCours: count(array_unique($datesDeCours)),
            semainesQualifiees: $semainesQualifiees,
            etudiantsRemunerateurs: $etudiantsRemunerateurs,
            decoupageSemaines: $map,
        );
    }
}
