<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §6 — Groups (class/cohort).
 * statut lifecycle: Pré-inscription → En formation → Fin de formation.
 * A group row is NEVER deleted (inscriptions.group_id must stay valid);
 * transition to "Fin de formation" must go through Group::archiverCommeTermine().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 150);
            $table->string('niveau', 10);
            $table->foreignId('enseignant_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('salle_id')->nullable()->constrained('salles')->nullOnDelete();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
            $table->integer('capacite_max')->nullable();
            $table->string('statut', 20)->default('Pré-inscription');
            $table->date('date_debut_formation')->nullable();
            $table->date('date_fin_formation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
