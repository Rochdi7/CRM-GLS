<?php

declare(strict_types=1);

use App\Models\Caisse;
use App\Services\CaisseProvisioner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Payment-method accounts per centre (24/08/2026 refactor — see
 * docs/caisse-comptes-methode-architecture.md).
 *
 * TPE / Chèque / Virement become real `caisses` rows, ONE per centre, so a
 * card/cheque/bank payment credits its own account instead of the
 * cashier's physical till. Two PostgreSQL guards make "one dirham = one
 * row" structural:
 *  - a partial UNIQUE on (etablissement_id, type) for the three method
 *    types — a centre can never hold two TPE accounts;
 *  - a CHECK that a method account always belongs to a centre and never to
 *    an employee (it is not somebody's till).
 * Then every existing centre gets its three accounts (idempotent — safe to
 * re-run on production with `migrate --force`).
 *
 * Existing balances are NOT touched here: re-homing historical non-cash
 * rows is the job of `php artisan caisse:recalculer-soldes` (dry-run by
 * default), which journals every movement through CaisseLedger.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Literals, not Caisse::TYPES_METHODE: a migration must keep running
        // even if the constants are renamed later.
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX caisses_methode_par_centre_unique
            ON caisses (etablissement_id, type)
            WHERE type IN ('TPE', 'Chèque', 'Virement')
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE caisses
            ADD CONSTRAINT caisses_compte_methode_centre_check
            CHECK (
                type NOT IN ('TPE', 'Chèque', 'Virement')
                OR (etablissement_id IS NOT NULL AND responsable_employee_id IS NULL)
            )
        SQL);

        $provisioner = app(CaisseProvisioner::class);

        foreach (DB::table('etablissements')->orderBy('id')->pluck('id') as $etablissementId) {
            foreach (Caisse::TYPES_METHODE as $methode) {
                $provisioner->compteMethodeFor((int) $etablissementId, $methode);
            }
        }
    }

    public function down(): void
    {
        // The accounts themselves are money records and are NOT deleted on
        // rollback (CLAUDE.md §11) — only the guards are lifted.
        DB::statement('ALTER TABLE caisses DROP CONSTRAINT IF EXISTS caisses_compte_methode_centre_check');
        DB::statement('DROP INDEX IF EXISTS caisses_methode_par_centre_unique');
    }
};
