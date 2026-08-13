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
            // Per-group due date: different groups can bill the same catalog
            // fee with different échéances.
            $table->date('date_echeance')->nullable();
            // Per-group classification (e.g. CEFR niveau) — plain VARCHAR
            // validated against Group::NIVEAUX (schema §5, no lookup table).
            $table->string('classification', 10)->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'frais_id']);
            $table->index('frais_id', 'group_frais_frais_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_frais');
    }
};
