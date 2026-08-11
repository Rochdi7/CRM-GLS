<?php

declare(strict_types=1);

namespace App\Domain\Shared\Support;

use Illuminate\Support\Facades\DB;

/**
 * Generates sequential human-readable reference codes for the `reference`
 * columns (employees, students, inscriptions, encaissements, depenses,
 * remboursements, caisse_transfers) — e.g. ETU-042.
 *
 * References are system-generated, never typed by users.
 */
final class ReferenceGenerator
{
    public static function make(string $prefix, string $table): string
    {
        $next = (int) DB::table($table)->max('id') + 1;

        do {
            $reference = sprintf('%s-%03d', $prefix, $next);
            $exists = DB::table($table)->where('reference', $reference)->exists();
            $next++;
        } while ($exists);

        return $reference;
    }
}
