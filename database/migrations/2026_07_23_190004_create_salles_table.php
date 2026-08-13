<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// gls-crm-schema.md §3 — Rooms (every room belongs to exactly one branch)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salles', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 100);
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->integer('capacite')->nullable();
            $table->string('statut', 20)->default('Active');
            $table->timestamps();

            $table->index('etablissement_id', 'salles_etablissement_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salles');
    }
};
