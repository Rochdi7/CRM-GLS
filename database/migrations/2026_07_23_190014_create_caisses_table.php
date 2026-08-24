<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §10 — Cash registers / tills.
 * `solde` is a stored, application-updated number (NOT computed from a ledger)
 * — deliberate simplicity trade-off; every encaissement/depense/remboursement/
 * transfer MUST update it in the same transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisses', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 100);
            // "Comptes de caisse" tells an employee's personal till apart from
            // the standing bank / cheque / external accounts money also lands
            // in. Plain VARCHAR validated against Caisse::TYPES, like every
            // other statut/categorie in this schema (see the Deliberate
            // Simplifications table in gls-crm-schema.md) — no lookup table.
            // Literal, not Caisse::TYPE_CAISSIERE: a migration must keep running even
            // if the model's constant is later renamed or the class removed.
            $table->string('type', 30)->default('Caissière');
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('responsable_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('solde', 12, 2)->nullable()->default(0);
            $table->string('statut', 20)->default('Active');
            $table->timestamps();

            $table->index('etablissement_id', 'caisses_etablissement_id_idx');
            $table->index('responsable_employee_id', 'caisses_responsable_employee_id_idx');
            $table->index('type', 'caisses_type_idx');
        });

        // Payment-method accounts per centre (TPE / Chèque / Virement — see
        // docs/caisse-comptes-methode-architecture.md). Two PostgreSQL guards
        // make "one dirham = one row" structural:
        //  - a partial UNIQUE on (etablissement_id, type) for the three method
        //    types — a centre can never hold two TPE accounts;
        //  - a CHECK that a method account always belongs to a centre and never
        //    to an employee (it is not somebody's till).
        // Literals, not Caisse::TYPES_METHODE, for the same reason as above.
        // The accounts themselves are provisioned with the centre
        // (EtablissementObserver → CaisseProvisioner), never here.
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
    }

    public function down(): void
    {
        Schema::dropIfExists('caisses');
    }
};
