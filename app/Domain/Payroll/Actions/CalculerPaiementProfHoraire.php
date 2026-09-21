<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Actions;

/**
 * Calcul « Paiement prof » — mode HORAIRE.
 *
 *     total = taux horaire × heures
 *
 * Ce mode n'a pas de lignes par étudiant : l'enseignant est payé pour ses
 * HEURES, pas pour les présences de ses étudiants. Les heures sont néanmoins
 * PRÉ-CALCULÉES depuis les séances réellement effectuées du groupe
 * (`GetPaiementProfCalcul`), parce que nous possédons la donnée — le portail
 * GLS, lui, faisait saisir le total à la main.
 *
 * ⚠ Comme le mode hebdomadaire : rien n'est persisté, aucune caisse n'est
 * touchée. C'est une proposition de montant (§11).
 */
final class CalculerPaiementProfHoraire
{
    public function handle(float $tauxHoraire, float $heures): float
    {
        return round($tauxHoraire * $heures, 2);
    }
}
