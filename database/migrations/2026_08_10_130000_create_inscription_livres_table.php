<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Books (StockArticle rows of the "Livre" type) assigned to a registration.
 * One row per assigned book — the unique constraint prevents the same
 * physical stock row from being decremented twice for the same inscription
 * (re-editing only ever adds NEW rows for newly selected books; removing a
 * row is how AssignerLivresInscription restores stock). A level change that
 * needs a different book title uses a DIFFERENT stock_article_id (each
 * center's book title is its own StockArticle row) — never re-adds the same
 * one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscription_livres', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('stock_article_id')->constrained('stock_articles')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->unique(['inscription_id', 'stock_article_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscription_livres');
    }
};
