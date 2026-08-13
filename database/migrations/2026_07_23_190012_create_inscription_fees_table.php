<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// gls-crm-schema.md §9 — Fee line items per enrollment (payments allocate per-fee)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscription_fees', function (Blueprint $table): void {
            $table->id();
            // cascade: a fee line has no meaning without its enrollment (structure doc §2)
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->string('nom', 150); // e.g. "Frais de Juillet"
            // Discount trail (pic 3): montant = montant_initial − remise (pct
            // applied first, else fixed DH) — final amount stored in `montant`.
            $table->decimal('montant_initial', 10, 2)->nullable();
            $table->decimal('remise_pct', 5, 2)->nullable();
            $table->decimal('remise_montant', 10, 2)->nullable();
            $table->foreignId('frais_id')->nullable()->constrained('frais')->nullOnDelete();
            $table->decimal('montant', 10, 2);
            $table->date('date_echeance');
            $table->string('note', 255)->nullable();
            $table->string('statut', 20)->default('Non payé'); // Non payé / Payé partiellement / Payé
            // "Hide" instead of hard-delete — a hidden fee keeps its row and
            // payment history intact; restorable from "Frais masqués".
            $table->timestamp('masque_le')->nullable();
            $table->timestamps();

            $table->index('inscription_id', 'inscription_fees_inscription_id_idx');
            $table->index('frais_id', 'inscription_fees_frais_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscription_fees');
    }
};
