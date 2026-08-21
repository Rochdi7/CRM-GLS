<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Fee catalog (predefined "frais") — the managed list of fee types
 * (Frais annuel, Frais d'inscription, Frais de Juillet, Frais dexam ÖSD…).
 * Catalog entries are ASSIGNED to groups (group_frais pivot); a group's
 * assigned fees become the "Frais disponibles" when enrolling a student.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 150);
            // DEFAULT amount only. Resolution order when a group needs a price:
            // frais_etablissement.montant (this centre) -> montant_defaut -> 0,
            // and group_frais.montant stays the final authority for what a
            // given group actually charges. A catalog entry left at 0.00 is
            // invisible to the payment modal, whose unpaid-fees query keeps
            // only fees where reste = montant - paye > 0.
            $table->decimal('montant_defaut', 10, 2)->default(0);
            $table->string('statut', 20)->default('Actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais');
    }
};
