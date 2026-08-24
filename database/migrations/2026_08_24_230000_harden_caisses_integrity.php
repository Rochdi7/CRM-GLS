<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Financial-integrity hardening of `caisses` (24/08/2026 audit) — two
 * PostgreSQL guards that make application invariants structural:
 *
 *  1. `solde` NOT NULL. The column was created nullable with a default of
 *     0; a NULL balance is meaningless ("(float) null" silently reads 0 and
 *     the next ledger movement would journal a false « avant »). Any NULL
 *     row is set to 0.00 first, so this is safe on existing data.
 *
 *  2. ONE physical till per employee: a partial UNIQUE on
 *     `responsable_employee_id` restricted to type 'Caissière'. The
 *     provisioner's "exists() then create()" is not atomic — two concurrent
 *     requests (observer + first « Ma caisse » visit, or the retro command
 *     racing a login) could each create a till, and the employee's cash
 *     would then be split across two rows picked non-deterministically.
 *     An « Externe » safe assigned to the same employee is unaffected (the
 *     index is partial). NULL responsables (legacy tills) are ignored by
 *     PostgreSQL's unique semantics.
 *
 *     ⚠ If the production database ALREADY holds two Caissière rows for the
 *     same employee, the index cannot be created and this migration STOPS
 *     with a message listing them. That is deliberate: silently skipping the
 *     guard would leave the double-till hazard in place, and merging two
 *     money accounts is a business decision (which one keeps the history?)
 *     — not something a migration may guess. Resolve by hand (a validated
 *     transfer to empty one, then `caisse:verifier-coherence`), re-run.
 *
 * Literals rather than model constants: a migration must keep running even
 * if the constants are renamed later.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE caisses SET solde = 0 WHERE solde IS NULL');
        DB::statement('ALTER TABLE caisses ALTER COLUMN solde SET NOT NULL');
        DB::statement('ALTER TABLE caisses ALTER COLUMN solde SET DEFAULT 0');

        $duplicates = DB::table('caisses')
            ->selectRaw('responsable_employee_id, COUNT(*) AS n')
            ->where('type', 'Caissière')
            ->whereNotNull('responsable_employee_id')
            ->groupBy('responsable_employee_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('n', 'responsable_employee_id');

        if ($duplicates->isNotEmpty()) {
            $list = $duplicates->map(fn ($n, $employeeId) => "employé #{$employeeId} ({$n} caisses)")->implode(', ');

            throw new RuntimeException(
                'Impossible de garantir « une caisse physique par employé » : plusieurs caisses Caissière existent pour '
                .$list.'. Fusionnez-les à la main (transfert validé pour en vider une), puis relancez la migration.',
            );
        }

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX IF NOT EXISTS caisses_une_caissiere_par_employe
            ON caisses (responsable_employee_id)
            WHERE type = 'Caissière' AND responsable_employee_id IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS caisses_une_caissiere_par_employe');
        DB::statement('ALTER TABLE caisses ALTER COLUMN solde DROP NOT NULL');
    }
};
