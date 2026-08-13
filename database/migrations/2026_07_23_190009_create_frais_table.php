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
            $table->string('statut', 20)->default('Actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais');
    }
};
