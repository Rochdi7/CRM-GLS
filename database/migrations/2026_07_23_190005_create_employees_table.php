<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// gls-crm-schema.md §4 — Master staff record (teachers are employees, categorie distinguishes roles)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('sexe', 10)->nullable();
            $table->date('date_naissance')->nullable();
            $table->date('date_embauche')->nullable();
            $table->decimal('salaire', 10, 2)->nullable();
            $table->string('categorie', 30); // Enseignant / Directeur / Assistante administrative / ...
            $table->string('statut', 20)->default('Actif');
            $table->string('telephone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('note')->nullable();
            $table->string('adresse', 255)->nullable();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('etablissement_id', 'employees_etablissement_id_idx');
            $table->index('user_id', 'employees_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
