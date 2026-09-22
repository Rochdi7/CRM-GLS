<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Actions;

use App\Domain\Payroll\DTOs\LignePaiementProf;
use App\Domain\Payroll\DTOs\ResultatPaiementProf;

/**
 * Calcul « Paiement prof » — PROPORTIONNEL AUX SÉANCES (règle arrêtée le
 * 22/09/2026, décision du CEO).
 *
 *     montant par séance = taux par étudiant ÷ nombre de séances du mois
 *     montant étudiant   = montant par séance × ses présences
 *
 * Exemple : taux 500 DH, 22 séances ⇒ 22,73 DH la séance ; un étudiant
 * présent 18 fois rapporte 18 × 22,73 = 409,09 DH.
 *
 * ⚠ Ceci REMPLACE le modèle hebdomadaire porté du portail GLS
 * (`CalculerPaiementProfHebdomadaire`, 4 semaines + seuil de 3 jours), qui
 * ne correspondait pas à la pratique de GLS. Les deux différences de fond :
 *
 *  - il n'y a plus de SEUIL : chaque présence paie, il n'existe plus de
 *    semaine « ratée de justesse » qui ne rapporte rien ;
 *  - il n'y a plus de SEMAINES du tout : le diviseur est le nombre de
 *    séances, donc un férié ou un mois court se règle tout seul — la
 *    question « cette semaine a-t-elle été enseignée ? » ne se pose plus.
 *
 * **Le diviseur est PLAFONNÉ à 22 séances** (`SEANCES_MAX_PAR_MOIS`) : « un
 * mois compte 22 séances au maximum ». Un mois plus court divise par son
 * nombre RÉEL de séances — un étudiant présent partout vaut alors exactement
 * le taux, l'enseignant n'étant pas pénalisé par un mois creux. Un mois
 * exceptionnellement chargé (rattrapages) divise par 22 et non par 25 : au
 * delà du plafond, les séances supplémentaires ne diluent pas la paie, si
 * bien qu'un étudiant présent partout peut dépasser le taux — c'est voulu,
 * il a suivi plus de cours que le mois n'en compte normalement.
 *
 * ⚠ Cette action NE PERSISTE RIEN et ne touche AUCUNE caisse. Elle produit
 * un montant ; c'est l'utilisateur qui décide ensuite d'enregistrer la
 * dépense par le chemin ordinaire (`EnregistrerDepense`), avec tous ses
 * invariants monétaires (§11). Un calcul n'est pas un paiement.
 */
final class CalculerPaiementProfParSeance
{
    /**
     * Plafond de séances rémunérées dans un mois.
     *
     * Décision métier du 22/09/2026 : « chaque mois a 22 séances au
     * maximum ». Sert de DIVISEUR maximal — jamais de minimum, sinon un mois
     * court paierait moins que ce qu'il a enseigné.
     */
    public const SEANCES_MAX_PAR_MOIS = 22;

    /**
     * @param  array<int, array{student_id: int, nom: string, retenus: int, absents: int, ignores: int}>  $etudiants
     * @param  int  $nombreSeances  séances RÉELLEMENT effectuées sur la période
     * @param  array<int, float|null>  $ajustements  student_id => montant imposé
     */
    public function handle(
        array $etudiants,
        int $nombreSeances,
        float $montantParEtudiant,
        array $ajustements = [],
    ): ResultatPaiementProf {
        // Le diviseur : le réel, borné par le plafond mensuel.
        $diviseur = min(max($nombreSeances, 0), self::SEANCES_MAX_PAR_MOIS);

        // Un mois sans séance ne paie rien — et ne divise surtout pas par 0.
        $montantParSeance = $diviseur > 0
            ? round($montantParEtudiant / $diviseur, 2)
            : 0.0;

        $lignes = [];
        $total = 0.0;
        $etudiantsRemunerateurs = 0;

        foreach ($etudiants as $etudiant) {
            // Les présences AU-DELÀ du plafond ne sont pas rognées : si le
            // mois a eu 25 séances, un étudiant présent 25 fois est payé 25
            // fois la part calculée sur 22. Il a suivi plus que le mois
            // standard, il rapporte plus.
            $presences = max($etudiant['retenus'], 0);

            // ⚠ Le produit se calcule sur le taux ENTIER, pas sur la part
            // arrondie : 500 / 22 = 22,7272… et 22,73 × 22 rendrait 500,06.
            // Un étudiant présent à TOUTES les séances doit valoir EXACTEMENT
            // le taux — sinon le total du mois tombe sur un chiffre que
            // personne ne sait expliquer, et « présent partout » ne se lit
            // plus comme « le prix plein ».
            //
            // Au-delà du plafond (mois à 25 séances), la division reste faite
            // sur 22 : les séances supplémentaires rapportent en plus.
            $auto = $diviseur > 0
                ? round($montantParEtudiant * $presences / $diviseur, 2)
                : 0.0;

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
            montantParSeance: $montantParSeance,
            nombreSeances: $nombreSeances,
            seancesRemunerees: $diviseur,
            etudiantsRemunerateurs: $etudiantsRemunerateurs,
        );
    }
}
