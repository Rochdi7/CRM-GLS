<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §13 — Expenses (cash outflows).
 * mots_cles is comma-separated free text by design (no tags table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('type_depense_id')->constrained('types_depenses')->restrictOnDelete();
            $table->foreignId('caisse_id')->constrained('caisses')->restrictOnDelete();
            $table->decimal('montant', 12, 2);
            // Approval workflow (CLAUDE.md §11 finance invariants). "En attente"
            // HOLDS the money — the till is not debited — until a super-admin
            // approves ("Approuvee", the ledger debit happens then) or refuses
            // ("Refusee", no movement ever). When the Parametres switch
            // AppSettings::EXPENSE_APPROVAL is OFF, rows are created directly
            // as Approuvee and the till is debited immediately.
            // Literal, not Depense::STATUT_APPROUVEE — see caisses.type.
            $table->string('statut', 20)->default('Approuvée');
            $table->string('methode_paiement', 20)->nullable();
            $table->date('date_depense');
            // "Paiement prof" only — the teaching PERIOD the payment covers,
            // as opposed to date_depense (the day the money was paid out).
            // Nullable because an ordinary dépense has no period at all
            // (StoreDepenseRequest requires them only for that type).
            $table->date('periode_debut')->nullable();
            $table->date('periode_fin')->nullable();
            $table->string('reference_facture', 100)->nullable();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('description', 255)->nullable();
            $table->string('mots_cles', 255)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('agent_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('motif_refus', 255)->nullable();
            $table->timestamps();

            $table->index(['caisse_id', 'date_depense'], 'depenses_caisse_date_idx');
            $table->index('date_depense', 'depenses_date_depense_idx');
            $table->index('type_depense_id', 'depenses_type_depense_id_idx');
            $table->index('agent_id', 'depenses_agent_id_idx');
            $table->index('group_id', 'depenses_group_id_idx');
            // The Depenses list filters on statut constantly (pending inbox)
            // and the finance queries pair it with the date — PostgreSQL does
            // not index a FK/filter column on its own (CLAUDE.md §17).
            $table->index(['statut', 'date_depense']);
            $table->index('approved_by', 'depenses_approved_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
