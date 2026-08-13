<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// gls-crm-schema.md §14 — Refunds to students (separate audit trail from depenses)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remboursements', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('beneficiaire_id')->constrained('students')->restrictOnDelete();
            // Links the refund back to the specific payment it's refunding;
            // nullable — a refund unrelated to any tracked payment is allowed.
            $table->foreignId('encaissement_id')->nullable()->constrained('encaissements')->nullOnDelete();
            $table->foreignId('caisse_id')->constrained('caisses')->restrictOnDelete();
            $table->decimal('montant', 12, 2);
            $table->date('date_remboursement');
            $table->string('motif', 255)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('agent_id')->constrained('employees')->restrictOnDelete();
            $table->timestamps();

            $table->index(['caisse_id', 'date_remboursement'], 'remboursements_caisse_date_idx');
            $table->index('beneficiaire_id', 'remboursements_beneficiaire_id_idx');
            $table->index('agent_id', 'remboursements_agent_id_idx');
            $table->index('encaissement_id', 'remboursements_encaissement_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remboursements');
    }
};
