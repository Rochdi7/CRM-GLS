<?php

declare(strict_types=1);

namespace App\Domain\Settings\Support;

use Carbon\CarbonImmutable;

/**
 * Derives a monthly fee's default due date from its own name.
 *
 * The catalog names monthly fees after the month they cover ("Frais de
 * Septembre", "Frais d'Octobre"…), so the due date is already implied and
 * should not have to be typed for all twelve of them on every group:
 *
 *   month → taken from the fee's name (Septembre = 9, Janvier = 1, …)
 *   day   → the group's own date_debut_formation (its "jj")
 *   year  → the calendar year that month falls in FOR THIS GROUP, derived
 *            from date_debut_formation, or from the group's académic year
 *            when it has none — see anneeFor() below.
 *
 * A fee whose name carries no month (inscription, annuel, exam…) has no
 * derivable due date and returns null, leaving it to be set by hand.
 */
final class FraisEcheanceResolver
{
    /**
     * Month names as they appear in the catalog, lowercased and without
     * accents so "Décembre"/"decembre"/"DÉCEMBRE" all match.
     *
     * @var array<string, int>
     */
    private const array MOIS = [
        'janvier' => 1,
        'fevrier' => 2,
        'mars' => 3,
        'avril' => 4,
        'mai' => 5,
        'juin' => 6,
        'juillet' => 7,
        'aout' => 8,
        'septembre' => 9,
        'octobre' => 10,
        'novembre' => 11,
        'decembre' => 12,
    ];

    /**
     * @param  string  $fraisNom  e.g. "Frais de Septembre"
     * @param  ?string $dateDebutFormation  the group's start date (Y-m-d)
     * @param  ?string $debutAnneeScolaire  the group's académic year start
     *                 (Y-m-d) — the fallback anchor when the group carries no
     *                 start date of its own
     * @return ?string Y-m-d, or null when the name carries no month
     */
    public static function defaultFor(
        string $fraisNom,
        ?string $dateDebutFormation,
        ?string $debutAnneeScolaire = null,
    ): ?string {
        $mois = self::moisFromNom($fraisNom);

        if ($mois === null) {
            return null;
        }

        $jour = self::jourFrom($dateDebutFormation);
        $annee = self::anneeFor($mois, $dateDebutFormation, $debutAnneeScolaire);

        // Clamp to the month's real length so "31" in February becomes the
        // 28th/29th instead of overflowing into March.
        $jour = min($jour, (int) CarbonImmutable::create($annee, $mois, 1)->daysInMonth);

        return CarbonImmutable::create($annee, $mois, $jour)->toDateString();
    }

    /**
     * The calendar year the given month falls in for a group starting on
     * $dateDebutFormation.
     *
     * A school year straddles TWO calendar years: a group starting in
     * September 2025 runs Septembre–Décembre in 2025 and Janvier–Août in
     * 2026. Anchoring on the current year instead (what this did until
     * 27/08/2026) stamped every month with the same year, so « Frais de
     * Septembre » landed nine months AFTER « Frais de Janvier » and every
     * screen that orders fees by due date — the group fee table, the
     * inscription form, « Statistique de groupe » — read the school year
     * backwards, opening on Janvier instead of on the group's real first
     * month.
     *
     * Rule: months at or after the start month belong to the start year,
     * earlier months roll into the next one.
     *
     * Anchor preference:
     *   1. the group's own date_debut_formation — the real first lesson;
     *   2. the group's ACADÉMIC YEAR start (September) — which is what
     *      defines the school year in the first place, and which every group
     *      has even though date_debut_formation is optional and in practice
     *      almost never filled in;
     *   3. the current calendar year, when neither is known.
     */
    private static function anneeFor(
        int $mois,
        ?string $dateDebutFormation,
        ?string $debutAnneeScolaire = null,
    ): int {
        $debut = self::debutFrom($dateDebutFormation) ?? self::debutFrom($debutAnneeScolaire);

        if ($debut === null) {
            return (int) CarbonImmutable::now()->year;
        }

        return $mois >= $debut->month ? $debut->year : $debut->year + 1;
    }

    /** The month a fee's name refers to, or null if it names none. */
    public static function moisFromNom(string $fraisNom): ?int
    {
        $normalized = self::normalize($fraisNom);

        foreach (self::MOIS as $nom => $numero) {
            // Word-boundary match so "Frais dexam ÖSD A1" can never be
            // mistaken for a month, and "Mai" never matches inside another
            // word.
            if (preg_match('/\b'.$nom.'\b/u', $normalized) === 1) {
                return $numero;
            }
        }

        return null;
    }

    /**
     * The sort key that puts a fee list in TEACHING-CALENDAR order — janvier
     * to décembre — instead of alphabetical (which interleaves "Avril",
     * "Août", "Octobre" meaninglessly).
     *
     * Fees whose name carries no month (inscription, annuel…) are not part of
     * the monthly cycle and sort FIRST (key 0), so the one-off charges a
     * student pays up front stay at the top of the table, ahead of the
     * twelve monthly instalments. EXAM fees (« Frais d'exam ÖSD A1 »…) are
     * the exception: an exam is settled at the very end of the training, so
     * they always sort LAST (key 99), after Décembre.
     */
    public static function ordreFromNom(string $fraisNom): int
    {
        if (self::isExamen($fraisNom)) {
            return self::ORDRE_EXAMEN;
        }

        return self::moisFromNom($fraisNom) ?? 0;
    }

    /** Sort key of exam fees — after every month (1…12). */
    public const int ORDRE_EXAMEN = 99;

    /** Whether the fee is an exam fee ("exam", "examen", "d'exam", "dexam"…). */
    public static function isExamen(string $fraisNom): bool
    {
        return str_contains(self::normalize($fraisNom), 'exam');
    }

    /** The group's start day, defaulting to the 1st when unknown. */
    private static function jourFrom(?string $dateDebutFormation): int
    {
        return self::debutFrom($dateDebutFormation)?->day ?? 1;
    }

    /** The group's start date, or null when absent/unparseable. */
    private static function debutFrom(?string $dateDebutFormation): ?CarbonImmutable
    {
        if ($dateDebutFormation === null || $dateDebutFormation === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($dateDebutFormation);
        } catch (\Throwable) {
            return null;
        }
    }

    /** Lowercases and strips accents so name matching is spelling-proof. */
    private static function normalize(string $value): string
    {
        $lower = mb_strtolower(trim($value));

        return strtr($lower, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'û' => 'u', 'ù' => 'u', 'ü' => 'u',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ç' => 'c',
        ]);
    }
}
