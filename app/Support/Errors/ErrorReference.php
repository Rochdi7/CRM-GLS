<?php

declare(strict_types=1);

namespace App\Support\Errors;

use Illuminate\Support\Str;

/**
 * The short support code shown on an error page.
 *
 * Reported 31/08/2026: users seeing a bare « 500 Erreur serveur » told the
 * office the SERVER WAS DOWN, when in reality one action had failed. Part of
 * the fix is wording (resources/views/errors/*), the other part is this code:
 * the page shows it, `$exceptions->context()` writes the SAME value into the
 * log line, so a user reporting « erreur GLS-3F2A9C41 » gives the maintainer a
 * string to grep for in laravel.log instead of a timestamp to guess at.
 *
 * Generated ONCE per request and memoised: the page and the log entry must
 * agree, and a fresh uuid on each call would silently break that.
 */
final class ErrorReference
{
    private static ?string $reference = null;

    public static function current(): string
    {
        return self::$reference ??= 'GLS-'.strtoupper(substr((string) Str::uuid(), 0, 8));
    }

    /**
     * Test seam only — the container is rebuilt between requests in production,
     * but a test process keeps this class in memory across several of them.
     */
    public static function flush(): void
    {
        self::$reference = null;
    }
}
