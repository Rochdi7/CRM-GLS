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
 *   year  → the current year
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
     * @return ?string Y-m-d, or null when the name carries no month
     */
    public static function defaultFor(string $fraisNom, ?string $dateDebutFormation): ?string
    {
        $mois = self::moisFromNom($fraisNom);

        if ($mois === null) {
            return null;
        }

        $jour = self::jourFrom($dateDebutFormation);
        $annee = (int) CarbonImmutable::now()->year;

        // Clamp to the month's real length so "31" in February becomes the
        // 28th/29th instead of overflowing into March.
        $jour = min($jour, (int) CarbonImmutable::create($annee, $mois, 1)->daysInMonth);

        return CarbonImmutable::create($annee, $mois, $jour)->toDateString();
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

    /** The group's start day, defaulting to the 1st when unknown. */
    private static function jourFrom(?string $dateDebutFormation): int
    {
        if ($dateDebutFormation === null || $dateDebutFormation === '') {
            return 1;
        }

        try {
            return (int) CarbonImmutable::parse($dateDebutFormation)->day;
        } catch (\Throwable) {
            return 1;
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
