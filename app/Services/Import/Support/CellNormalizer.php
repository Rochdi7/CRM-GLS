<?php

declare(strict_types=1);

namespace App\Services\Import\Support;

use App\Services\Import\Exceptions\ImportCellParseException;
use Carbon\CarbonImmutable;

/**
 * Pure, framework-free cell normalization for legacy-CRM XLSX exports.
 * Every method is static and takes/returns plain scalars so it can be unit
 * tested against real observed cell values without booting the app.
 */
final class CellNormalizer
{
    /**
     * Trim + collapse repeated internal whitespace, e.g. "HASNA  MACH" (two
     * spaces) -> "HASNA MACH", "HASNA " (trailing space) -> "HASNA".
     */
    public static function text(mixed $raw): string
    {
        $value = (string) ($raw ?? '');

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Parses the export's date formats. A literal "-" or empty string means
     * "no value" (seen on Date de naissance) and returns null; anything else
     * unparseable throws so the caller can turn it into an ERREUR row rather
     * than silently storing nonsense.
     */
    public static function parseDate(mixed $raw): ?CarbonImmutable
    {
        if ($raw instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($raw);
        }

        $value = self::text($raw);

        if ($value === '' || $value === '-') {
            return null;
        }

        // Defensive fallback: a future export might carry a real numeric
        // Excel date serial instead of an inline string (this sample set is
        // 100% inlineStr, so this branch has no coverage from real files
        // today, but costs nothing to keep).
        if (is_numeric($value)) {
            return CarbonImmutable::createFromDate(1899, 12, 30)->addDays((int) $value);
        }

        foreach (['d/m/Y H:i', 'd/m/Y'] as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, $value);
            } catch (\Throwable) {
                continue;
            }
        }

        throw new ImportCellParseException(sprintf('Date non reconnue : "%s".', $value));
    }

    /**
     * "1300 Dh" / "50 Dh" -> "1300.00" / "50.00". Never a float — a
     * number_format()-formatted decimal string, ready for a decimal:2 cast.
     */
    public static function parseMoney(mixed $raw): string
    {
        $value = self::text($raw);
        $stripped = (string) preg_replace('/\s*dh\s*$/iu', '', $value);
        $stripped = str_replace([' ', "\u{00A0}", "\u{202F}"], '', $stripped);
        $stripped = str_replace(',', '.', $stripped);

        if (! is_numeric($stripped)) {
            throw new ImportCellParseException(sprintf('Montant non reconnu : "%s".', $value));
        }

        return number_format((float) $stripped, 2, '.', '');
    }

    /**
     * Digits-only extraction -> +212XXXXXXXXX. Never throws — phone is
     * never a blocking field; returns null when nothing usable is left.
     */
    public static function normalizePhone(mixed $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($raw ?? ''));

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '212') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '+212'.substr($digits, 1);
        }

        if (str_starts_with($digits, '212')) {
            return '+'.$digits;
        }

        return $digits;
    }

    /**
     * "Élève / Payeur" doubling: the payer's full name appears twice,
     * whitespace-joined ("ABDERRAHMANE BOUGMA ABDERRAHMANE BOUGMA"). Splits
     * into whitespace tokens; collapses only when the token list splits
     * cleanly in half with both halves identical. Odd token counts or
     * mismatched halves (e.g. the real sample's "younes TALIB HACHIM
     * TALIBi") are left uncollapsed for manual resolution — never guessed
     * silently.
     *
     * @return array{value: string, collapsed: bool}
     */
    public static function collapseDoubledName(string $raw): array
    {
        $normalized = self::text($raw);
        $tokens = $normalized === '' ? [] : explode(' ', $normalized);
        $count = count($tokens);

        if ($count > 0 && $count % 2 === 0) {
            $half = intdiv($count, 2);
            $firstHalf = array_slice($tokens, 0, $half);
            $secondHalf = array_slice($tokens, $half);

            if ($firstHalf === $secondHalf) {
                return ['value' => implode(' ', $firstHalf), 'collapsed' => true];
            }
        }

        return ['value' => $normalized, 'collapsed' => false];
    }

    /**
     * Best-effort guess for the CONFLIT preview screen's prefilled text
     * field when collapseDoubledName() couldn't auto-collapse — the first
     * half (rounded up) of the whitespace tokens.
     */
    public static function bestEffortNameGuess(string $raw): string
    {
        $tokens = explode(' ', self::text($raw));
        $tokens = array_values(array_filter($tokens, fn (string $t): bool => $t !== ''));

        if ($tokens === []) {
            return '';
        }

        return implode(' ', array_slice($tokens, 0, (int) ceil(count($tokens) / 2)));
    }
}
