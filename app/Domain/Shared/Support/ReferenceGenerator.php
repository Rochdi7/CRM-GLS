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
 *
 * ⚠ Concurrency (05/10/2026): two cashiers saving in the same second both
 * read the same max(id) and computed the same reference — the second insert
 * died on `encaissements_reference_unique` (ENC-30500, GLS Marrakech) and the
 * payment was lost. Inside a transaction we now take a PostgreSQL advisory
 * lock per table, held until COMMIT, so the second caller waits for the
 * first row to be visible before computing its own number.
 */
final class ReferenceGenerator
{
    public static function make(string $prefix, string $table): string
    {
        // Transaction-scoped: released at commit/rollback. Outside a
        // transaction it would be released at once, so it only helps callers
        // that insert in the same transaction — every money action does.
        if (DB::transactionLevel() > 0) {
            DB::select('select pg_advisory_xact_lock(hashtext(?))', ['reference:'.$table]);
        }

        $next = (int) DB::table($table)->max('id') + 1;

        do {
            $reference = sprintf('%s-%03d', $prefix, $next);
            $exists = DB::table($table)->where('reference', $reference)->exists();
            $next++;
        } while ($exists);

        return $reference;
    }
}
