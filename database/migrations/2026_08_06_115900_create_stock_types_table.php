<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Stock type catalog — replaces StockArticle's old hardcoded CATEGORIES
 * array so new product types (books, supplies, equipment, …) can be added
 * without a code change, same pattern as types_depenses. is_system rows
 * (the original 6 categories) are seeded once and locked from admin
 * editing/deletion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_types', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 100);
            $table->boolean('is_system')->default(false);
            $table->string('statut', 20)->default('Actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_types');
    }
};
