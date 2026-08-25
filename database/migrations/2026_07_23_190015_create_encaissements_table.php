<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §11 — Payments received (cash / card / cheque unified).
 * Cheque data is inline by design (3 columns, no cheque lifecycle table).
 * NOTE (schema §11 flag): inscription_fee_id is REQUIRED per the approved v4
 * schema — if true unallocated advances are ever needed, this column must
 * become nullable (documented trade-off, do not change silently).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encaissements', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            // Legacy import provenance. legacy_ref is unique PER CENTRE like
            // students/inscriptions — every centre's old CRM numbers its
            // payments from P1, so « P3 » exists in all seven exports (the
            // 24/08/2026 Rabat import lost 4 297 rows to a global check).
            // etablissement_id is that dedupe scope (the student's centre at
            // payment time); money routing never reads it — caisse_id is the
            // account.
            $table->string('legacy_ref', 50)->nullable();
            $table->string('legacy_source', 30)->nullable();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            // Nullable: an "avance" is money received but not yet allocated to
            // any fee (gls-crm-schema.md §11's documented trade-off), applied
            // to a fee later via a second row carrying applied_from_encaissement_id.
            $table->foreignId('inscription_fee_id')->nullable()->constrained('inscription_fees')->restrictOnDelete();
            $table->foreignId('applied_from_encaissement_id')->nullable()->constrained('encaissements')->nullOnDelete();
            // Payments made using a tracked chèque link back to it — the sum
            // of these rows' montant is the chèque's used amount ("Reste" =
            // montant minus this sum), mirroring the avance applications() pattern.
            $table->foreignId('cheque_id')->nullable()->constrained('cheques')->restrictOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('methode', 30); // Espèces / TPE / Chèque / Virement
            $table->date('date_paiement');
            $table->foreignId('caisse_id')->constrained('caisses')->restrictOnDelete();
            $table->foreignId('agent_id')->constrained('employees')->restrictOnDelete();
            $table->string('numero_cheque', 50)->nullable();
            $table->string('banque', 100)->nullable();
            $table->date('date_echeance_cheque')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['caisse_id', 'date_paiement'], 'encaissements_caisse_date_idx');
            // Standalone date index: the composite above cannot serve the
            // dashboard aggregates / list ordering that do not filter by till.
            $table->index('date_paiement', 'encaissements_date_paiement_idx');
            $table->index('student_id', 'encaissements_student_id_idx');
            $table->index('inscription_fee_id', 'encaissements_inscription_fee_id_idx');
            $table->index('agent_id', 'encaissements_agent_id_idx');
            $table->index('applied_from_encaissement_id', 'encaissements_applied_from_idx');
            $table->index('cheque_id', 'encaissements_cheque_id_idx');
            $table->unique(['etablissement_id', 'legacy_ref'], 'encaissements_etab_legacy_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encaissements');
    }
};
