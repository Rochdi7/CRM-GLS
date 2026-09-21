<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Support;

use App\Models\Presence;

/**
 * Comment un statut d'appel est lu PAR LA PAIE.
 *
 * ⚠ Le CRM enregistre QUATRE statuts (Présent / Absent / Retard / Justifié)
 * là où le portail GLS n'en connaissait que deux. La règle de paie, décidée
 * le 21/09/2026, est volontairement BINAIRE et ne retient que « Présent » :
 *
 *   - « Présent »  → compte comme jour retenu ;
 *   - « Absent »   → compte comme jour NON retenu ;
 *   - « Retard » et « Justifié » → IGNORÉS, ni pour ni contre.
 *
 * « Ignoré » ne veut PAS dire « absent ». La ligne est retirée du calcul
 * comme si l'étudiant n'avait pas été appelé ce jour-là : elle ne compte pas
 * vers le seuil hebdomadaire, mais ne compte pas non plus CONTRE lui. Une
 * semaine de 3 « Présent » + 2 « Retard » qualifie donc exactement comme une
 * semaine de 3 « Présent » seuls.
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
     * Le statut est-il écarté du calcul (ni pour, ni contre) ?
     *
     * Une ligne ignorée n'entre pas non plus dans le dénominateur : le jour
     * se lit comme un jour sans appel pour CET étudiant.
     */
    public static function estIgnore(?string $statut): bool
    {
        return $statut === Presence::STATUT_RETARD
            || $statut === Presence::STATUT_JUSTIFIE;
    }

    /**
     * Statuts effectivement pris en compte par une requête de paie.
     *
     * @return list<string>
     */
    public static function statutsComptes(): array
    {
        return [Presence::STATUT_PRESENT, Presence::STATUT_ABSENT];
    }
}
