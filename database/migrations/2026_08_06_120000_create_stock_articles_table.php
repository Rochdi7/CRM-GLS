<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stock module — inventory items. `quantite` is application-maintained
// (same pattern as caisses.solde): every change goes through the
// EnregistrerMouvementStock action, which writes the stock_mouvements line
// and adjusts the quantity in ONE transaction.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique(); // ART-000001, system-generated
            $table->string('nom', 150);
            $table->string('categorie', 50); // plain VARCHAR, StockArticle::CATEGORIES
            $table->integer('quantite')->default(0);
            $table->integer('seuil_alerte')->nullable(); // low-stock warning threshold
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->string('statut', 20)->default('Actif'); // Actif / Inactif
            $table->text('note')->nullable();
            $table->timestamps();

            // FK columns are not auto-indexed on PostgreSQL (§17).
            $table->index('etablissement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_articles');
    }
};
