<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-center pricing for the fee catalog.
 *
 * The same fee ("Frais de Septembre") is charged at a different amount
 * depending on the branch — Rabat/Casablanca 1400, Kénitra/Marrakech/Salé
 * 1300, Agadir 1200. Duplicating the catalog once per center would fork
 * 17 rows into 119 and break every existing group_frais link, so instead
 * ONE catalog entry is attached to the centers where it applies, and this
 * pivot carries that center's own amount.
 *
 * Resolution order when a group needs an amount for a fee:
 *   frais_etablissement.montant (this center) → frais.montant_defaut → 0
 *
 * frais.montant_defaut therefore stays meaningful: it is the fallback for
 * a center with no explicit line, and group_frais.montant remains the
 * final authority for what a given group actually charges.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frais_etablissement', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('frais_id')->constrained('frais')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->decimal('montant', 10, 2)->default(0);
            $table->timestamps();

            // One price per fee per center.
            $table->unique(['frais_id', 'etablissement_id']);
            // PostgreSQL does not index the referencing side of a FK, and
            // the center-scoped lookup ("all fees priced for this branch")
            // filters on etablissement_id alone. frais_id is already
            // covered by the unique index above.
            $table->index('etablissement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais_etablissement');
    }
};
