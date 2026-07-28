<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Which catalog fees apply to a group, and the amount for THIS group
 * (a group can override the catalog's default montant). These become the
 * "Frais disponibles" when enrolling a student into the group.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_frais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('frais_id')->constrained('frais')->cascadeOnDelete();
            $table->decimal('montant', 10, 2); // amount for this group
            $table->timestamps();

            $table->unique(['group_id', 'frais_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_frais');
    }
};
