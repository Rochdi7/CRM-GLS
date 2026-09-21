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
        $map = DecoupageSemaines::construire($datesDeCours, $seuil);

        // ⚠ Le montant d'une semaine se divise par les semaines RÉELLEMENT
        // ENSEIGNÉES, pas par 4 en aveugle (correctif du 21/09/2026, voir
        // DecoupageSemaines::bucketsOccupes()).
        //
        // Un mois amputé d'un férié tient dans 3 buckets : compter le 4e,
        // vide, comme une semaine non qualifiée faisait perdre un quart de sa
        // paie à un enseignant dont tous les étudiants étaient présents à
        // TOUS les cours. Diviser par le nombre de buckets occupés garantit
        // l'invariant qui donne son sens au calcul : un étudiant présent
        // partout vaut exactement le montant par étudiant, férié ou non.
        //
        // Sur un mois normal (4 buckets occupés) le résultat est identique
        // au portail — 25 % par semaine.
        $bucketsOccupes = DecoupageSemaines::bucketsOccupes($map);
        $nombreOccupes = count($bucketsOccupes);

        $montantSemaine = $nombreOccupes > 0
            ? round($montantParEtudiant / $nombreOccupes, 2)
            : round($montantParEtudiant * $pourcentageHebdo / 100, 2);

        $lignes = [];
        $total = 0.0;
        $semainesQualifiees = 0;
        $etudiantsRemunerateurs = 0;

        foreach ($etudiants as $etudiant) {
            $comptes = DecoupageSemaines::compter($map, $etudiant['semaines']);

            $montantsAuto = [];
            $totalAuto = 0.0;
            $qualifieAuMoinsUneSemaine = false;
            $qualifieesPourCetEtudiant = 0;

            foreach ($comptes as $semaine => $jours) {
                // Un bucket qui n'a reçu AUCUN jour de cours ne correspond à
                // aucune semaine enseignée : il n'est ni gagné ni perdu, et
                // n'apparaît pas comme une semaine ratée à l'écran.
                if (! in_array($semaine, $bucketsOccupes, true)) {
                    $montantsAuto[$semaine] = null;

                    continue;
                }

                $qualifie = $jours >= $seuil;
                $montantsAuto[$semaine] = $qualifie ? $montantSemaine : 0.0;

                if ($qualifie) {
                    $totalAuto += $montantSemaine;
                    $semainesQualifiees++;
                    $qualifieesPourCetEtudiant++;
                    $qualifieAuMoinsUneSemaine = true;
                }
            }

            // L'arrondi par semaine peut faire dériver la somme de quelques
            // centimes (500 / 3 = 166,67 × 3 = 500,01). Un étudiant qualifié
            // sur TOUTES les semaines enseignées vaut EXACTEMENT le montant
            // par étudiant — sinon le total du mois ne retombe pas sur un
            // chiffre rond et personne ne sait d'où viennent les centimes.
            if ($nombreOccupes > 0 && $qualifieesPourCetEtudiant === $nombreOccupes) {
                $totalAuto = $montantParEtudiant;
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
            bucketsOccupes: $bucketsOccupes,
        );
    }
}
