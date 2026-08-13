<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §7 — Group archive snapshot.
 * Columns are COPIED at archive time on purpose: the snapshot must reflect
 * what was true when the group finished, even if the live row is edited later.
 * Rows are inserted exclusively by Group::archiverCommeTermine().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups_historique', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('nom', 150);
            $table->string('niveau', 10);
            $table->foreignId('enseignant_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
            $table->integer('nombre_etudiants_final')->nullable();
            $table->date('date_debut_formation')->nullable();
            $table->date('date_fin_formation')->nullable();
            $table->dateTime('archived_at');
            $table->foreignId('archived_by')->nullable()->constrained('employees')->nullOnDelete();

            $table->index('archived_at', 'groups_historique_archived_at_idx');
            $table->index('enseignant_id', 'groups_historique_enseignant_id_idx');
            $table->index('etablissement_id', 'groups_historique_etablissement_id_idx');
            $table->index('annee_scolaire_id', 'groups_historique_annee_scolaire_id_idx');
            $table->index('archived_by', 'groups_historique_archived_by_idx');
            $table->index('group_id', 'groups_historique_group_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups_historique');
    }
};
