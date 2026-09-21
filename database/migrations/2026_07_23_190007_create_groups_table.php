<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §6 — Groups (class/cohort).
 * statut lifecycle: En inscription → En formation → Fin de formation.
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
            // Taux de rémunération de l'enseignant POUR CE GROUPE (21/09/2026).
            // Le taux appartient au GROUPE, pas à l'employé : un même
            // enseignant est payé différemment selon le groupe qu'il anime
            // (niveau, volume, centre), et un taux porté par `employees`
            // réécrirait le passé de tous ses groupes d'un coup.
            //
            // ⚠ Ces colonnes ne sont QU'UN DÉFAUT de formulaire : le montant
            // effectivement payé est FIGÉ sur la dépense au moment du
            // paiement (`depenses.montant`), jamais relu depuis le groupe.
            // Corriger un taux ne doit donc jamais réécrire un paiement déjà
            // enregistré (§11 : les enregistrements monétaires sont
            // append-only).
            $table->decimal('montant_par_etudiant_prof', 10, 2)->nullable();
            $table->decimal('taux_horaire_prof', 10, 2)->nullable();
            $table->string('statut', 20)->default('En inscription');
            $table->date('date_debut_formation')->nullable();
            $table->date('date_fin_formation')->nullable();
            $table->timestamps();

            $table->index('enseignant_id', 'groups_enseignant_id_idx');
            $table->index('salle_id', 'groups_salle_id_idx');
            $table->index('etablissement_id', 'groups_etablissement_id_idx');
            $table->index('annee_scolaire_id', 'groups_annee_scolaire_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
