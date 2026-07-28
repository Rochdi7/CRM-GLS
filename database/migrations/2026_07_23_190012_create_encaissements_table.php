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
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('inscription_fee_id')->constrained('inscription_fees')->restrictOnDelete();
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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encaissements');
    }
};
