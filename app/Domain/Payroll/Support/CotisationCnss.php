<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Support;

/**
 * Cotisation CNSS d'un enseignant — un montant FIXE retenu sur son
 * « Paiement prof » quand il est déclaré (06/10/2026).
 *
 * L'employé qui saisit le paiement coche « Cotisation CNSS » : le total
 * proposé par le calcul baisse de ce montant, et la dépense enregistrée
 * porte ce qui a été retenu (`depenses.cnss_montant`). La case se trouve
 * sur les DEUX écrans — le calcul et le modal « Paiement prof » — parce que
 * l'oublier au calcul ne doit pas obliger à tout refaire.
 *
 * UNE définition : la valeur est servie aux pages (`cnssMontant`), jamais
 * recopiée côté React, et la ligne STOCKE le montant retenu — pas un simple
 * drapeau — pour que l'historique reste exact si ce montant change un jour.
 *
 * ⚠ Le montant stocké sur la dépense (`montant`) est le NET versé, celui
 * que la caisse débite. La cotisation est une retenue, pas un mouvement de
 * caisse : rien d'autre n'est écrit nulle part.
 */
final class CotisationCnss
{
    /** Cotisation mensuelle retenue, en MAD. */
    public const MONTANT = 1700.0;
}
