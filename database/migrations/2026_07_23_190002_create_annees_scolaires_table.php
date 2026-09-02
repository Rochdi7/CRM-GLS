<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// gls-crm-schema.md §2 — Academic Years
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('annees_scolaires', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 20); // e.g. "2025/2026"
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('par_defaut')->default(false);
            $table->boolean('inscription_ouverte')->default(true);
            // Année CLÔTURÉE : plus aucune écriture (création ou
            // modification) n'est acceptée dans cette année — ni par un
            // super-admin. Le verrou se lève uniquement en décochant la case
            // dans Paramètres → Années scolaires (geste explicite, audité).
            // Ajouté le 02/09/2026 après qu'une dépense a été saisie par
            // erreur sur une année passée restée sélectionnée dans le
            // sélecteur du haut.
            $table->boolean('cloturee')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('annees_scolaires');
    }
};
