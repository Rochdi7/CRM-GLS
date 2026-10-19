<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corbeille des inscriptions (19/10/2026) — append-only.
 *
 * Une inscription supprimée disparaît de `inscriptions` ; cette table en
 * garde UNE ligne par suppression : ce qu'était le dossier (référence,
 * étudiant, groupe, année, centre, statut, montant) et surtout QUI l'a
 * supprimé et QUAND. Elle alimente l'onglet « Corbeille » de la page
 * Inscriptions (liste + compteur par personne). Les noms (étudiant, groupe,
 * année) sont FIGÉS à l'écriture : une fois la ligne `inscriptions`
 * effacée, et si le groupe l'est à son tour, c'est la seule trace lisible.
 * Les FK sont en SET NULL pour la même raison — la ligne survit à tout.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions_corbeille', function (Blueprint $table): void {
            $table->id();
            // Id d'origine (la ligne `inscriptions` n'existe plus — pas de FK).
            $table->unsignedBigInteger('inscription_id');
            $table->string('reference', 30);
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->string('student_reference', 30)->nullable();
            $table->string('student_nom', 200)->nullable();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
            $table->string('annee_nom', 50)->nullable();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('groupe_nom', 200)->nullable();
            $table->string('statut', 20)->nullable();
            $table->date('date_inscription')->nullable();
            $table->decimal('montant_total', 12, 2)->nullable();
            // Paiements intégralement remboursés détachés par SupprimerInscription.
            $table->jsonb('paiements_detaches')->nullable();
            $table->foreignId('supprime_par_user_id')->nullable()->constrained('users')->nullOnDelete();
            // Libellé figé de l'auteur (nom + login) — survit à la suppression du compte.
            $table->string('supprime_par_label', 200)->nullable();
            $table->timestamp('supprime_le');
            $table->timestamps();

            $table->index('inscription_id');
            $table->index('student_id');
            $table->index('etablissement_id');
            $table->index('annee_scolaire_id');
            $table->index('supprime_par_user_id');
            $table->index('supprime_le');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions_corbeille');
    }
};
