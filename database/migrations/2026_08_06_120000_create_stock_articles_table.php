<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stock module — inventory items. `quantite` is application-maintained
// (same pattern as caisses.solde): every change goes through the
// EnregistrerMouvementStock action, which writes the stock_mouvements line
// and adjusts the quantity in ONE transaction. A book (or any other product)
// with per-center quantities is modeled as ONE StockArticle row PER center
// (same nom, different etablissement_id/quantite) — not a single row with a
// per-center pivot, mirroring how the rest of this schema keeps quantity
// application-maintained on the row itself.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique(); // ART-000001, system-generated
            $table->string('nom', 150);
            $table->foreignId('stock_type_id')->constrained('stock_types')->restrictOnDelete();
            $table->integer('quantite')->default(0);
            $table->integer('seuil_alerte')->nullable(); // low-stock warning threshold
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->string('statut', 20)->default('Actif'); // Actif / Inactif
            $table->text('note')->nullable();
            $table->timestamps();

            // FK columns are not auto-indexed on PostgreSQL (§17).
            $table->index('etablissement_id');
            $table->index('stock_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_articles');
    }
};
