<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// gls-crm-schema.md §1 — Branches / Centers
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etablissements', function (Blueprint $table): void {
            $table->id();
            $table->string('nom_centre', 150);
            $table->string('ville', 100);
            $table->string('adresse', 255)->nullable();
            $table->string('ice', 30)->nullable(); // Identifiant Commun de l'Entreprise
            $table->string('telephone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->boolean('siege_social')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etablissements');
    }
};
