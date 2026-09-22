<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Support;

use App\Models\Presence;

/**
 * Comment un statut d'appel est lu PAR LA PAIE.
 *
 * ⚠ Règle arrêtée le 22/09/2026 : **seul « Présent » rémunère**. Tout le
 * reste — « Absent », et les rares « Retard » / « Justifié » — ne rapporte
 * rien.
 *
 * C'est volontairement BINAIRE. La saisie d'appel n'offre d'ailleurs que
 * deux boutons (Présent / Absent, voir `Seances/Show.tsx`) : « Retard » et
 * « Justifié » restent des valeurs valides en base uniquement pour les
 * quelques lignes héritées de l'ancien import (14 « Retard » au 22/09/2026,
 * aucun « Justifié ») — personne ne peut plus en créer. Elles sont donc
 * lues comme des absences plutôt que de recevoir un traitement à part que
 * plus aucun écran n'alimente.
 *
 * ⚠ Ne JAMAIS supprimer les constantes `STATUT_RETARD` / `STATUT_JUSTIFIE`
 * du modèle : des lignes réelles les portent, et `Presence::STATUTS` sert à
 * la validation. « Retirer du système » veut dire « ne plus en produire et
 * ne plus les compter », pas « effacer l'historique ».
 *
 * C'est la SEULE définition de cette lecture : le calculateur et le
 * read-model qui peint la grille la partagent, sinon l'écran colorierait des
 * cases que le total ne compte pas (CLAUDE.md §5 — un read-model ne redérive
 * jamais une règle métier).
 */
final class StatutPresencePaie
{
    /** Le seul statut qui rémunère. */
    public static function estRetenu(?string $statut): bool
    {
        return $statut === Presence::STATUT_PRESENT;
    }

    /**
     * Statut hérité que plus aucune saisie ne produit.
     *
     * Conservé pour que la GRILLE puisse le peindre distinctement (il n'est
     * pas une absence saisie comme telle), mais il ne rapporte rien : côté
     * argent, `estRetenu()` est la seule question posée.
     */
    public static function estHerite(?string $statut): bool
    {
        return $statut === Presence::STATUT_RETARD
            || $statut === Presence::STATUT_JUSTIFIE;
    }
}
