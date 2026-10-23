<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Support;

use App\Models\Employee;
use App\Models\Group;

/**
 * Cotisation CNSS d'un enseignant — retenue sur son « Paiement prof » quand
 * il est déclaré (06/10/2026).
 *
 * ⚠ Depuis le 21/10/2026 le montant retenu est le SALAIRE de l'enseignant
 * payé (`employees.salaire`, champ « Salaire » de sa fiche Employé), et non
 * plus un montant fixe de 1 700 MAD. Un enseignant sans salaire (NULL ou
 * 0) ne peut pas avoir de retenue : la case est désactivée à l'écran avec
 * un rappel de saisir le salaire, et le serveur refuse la case cochée.
 *
 * L'employé qui saisit le paiement coche « Cotisation CNSS » : le total
 * proposé par le calcul baisse de ce montant, et la dépense enregistrée
 * porte ce qui a été retenu (`depenses.cnss_montant`). La case se trouve
 * sur les DEUX écrans — le calcul et le modal « Paiement prof » — parce que
 * l'oublier au calcul ne doit pas obliger à tout refaire.
 *
 * UNE définition : le montant est servi aux pages, jamais recalculé côté
 * React, et la ligne STOCKE le montant retenu — pas un simple drapeau —
 * pour que l'historique reste exact si le salaire change un jour.
 *
 * ⚠ Le montant stocké sur la dépense (`montant`) est le NET versé, celui
 * que la caisse débite. La cotisation est une retenue, pas un mouvement de
 * caisse : rien d'autre n'est écrit nulle part.
 */
final class CotisationCnss
{
    /** Montant retenu pour cet enseignant — son salaire, null s'il n'en a pas. */
    public static function montantPour(?Employee $enseignant): ?float
    {
        $salaire = $enseignant?->salaire;

        if ($salaire === null || (float) $salaire <= 0) {
            return null;
        }

        return round((float) $salaire, 2);
    }

    /**
     * L'enseignant PAYÉ — celui désigné (calcul ou ligne), sinon le prof
     * ACTUEL du groupe. Même repli qu'`EnregistrerDepense`, pour que la
     * retenue soit toujours celle du prof réellement payé.
     */
    public static function enseignantDuPaiement(?int $enseignantId, ?int $groupId): ?Employee
    {
        $enseignantId ??= $groupId === null
            ? null
            : Group::query()->whereKey($groupId)->value('enseignant_id');

        return $enseignantId === null ? null : Employee::query()->find($enseignantId);
    }
}
