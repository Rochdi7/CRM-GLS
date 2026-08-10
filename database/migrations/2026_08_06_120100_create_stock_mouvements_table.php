<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stock module — movement history (Entrée / Sortie / Ajustement). Movements
// are the audit trail of stock_articles.quantite: they are never edited or
// deleted (no update/destroy routes) — corrections use a compensating
// movement, mirroring the finance rule.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_mouvements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_article_id')->constrained('stock_articles')->restrictOnDelete();
            $table->string('type', 20); // Entrée / Sortie / Ajustement
            $table->integer('quantite'); // always positive; Ajustement carries the NEW total
            $table->integer('quantite_avant');
            $table->integer('quantite_apres');
            $table->string('note', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            // List is filtered per article and shown newest-first (§17).
            $table->index(['stock_article_id', 'created_at']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_mouvements');
    }
};
