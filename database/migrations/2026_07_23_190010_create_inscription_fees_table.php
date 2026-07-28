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
            $table->decimal('montant', 10, 2);
            $table->date('date_echeance');
            $table->string('statut', 20)->default('Non payé'); // Non payé / Payé partiellement / Payé
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscription_fees');
    }
};
