<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only (19/10/2026, le soir même du premier déploiement) : la mise
 * en PAUSE est celle d'une INSCRIPTION, pas d'une fiche étudiant.
 *
 * La version du matin archivait la FICHE (`students.archive_*`). Décision
 * du propriétaire quelques heures plus tard : c'est le DOSSIER qui
 * s'interrompt et reprend — l'étudiant reste un étudiant du centre, c'est
 * son inscription qui passe « Archivée » avec un motif et une date de
 * retour. Les quatre colonnes migrent donc de `students` vers
 * `inscriptions` ; `inscriptions.statut = 'Archivée'` existait déjà (valeur
 * historique de l'import legacy) et redevient sélectionnable, par
 * l'action dédiée seulement. Les colonnes `students` sont retirées : elles
 * n'ont jamais porté une ligne (déployées et remplacées le même soir).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('inscriptions', 'archive_motif')) {
                $table->text('archive_motif')->nullable()->after('motif_annulation');
            }

            if (! Schema::hasColumn('inscriptions', 'archive_date_retour')) {
                $table->date('archive_date_retour')->nullable()->after('archive_motif');
            }

            if (! Schema::hasColumn('inscriptions', 'archive_le')) {
                $table->timestamp('archive_le')->nullable()->after('archive_date_retour');
            }

            if (! Schema::hasColumn('inscriptions', 'archive_par_user_id')) {
                $table->foreignId('archive_par_user_id')
                    ->nullable()
                    ->after('archive_le')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::table('students', function (Blueprint $table): void {
            if (Schema::hasColumn('students', 'archive_par_user_id')) {
                $table->dropConstrainedForeignId('archive_par_user_id');
            }

            foreach (['archive_le', 'archive_date_retour', 'archive_motif'] as $column) {
                if (Schema::hasColumn('students', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('inscriptions', 'archive_par_user_id')) {
                $table->dropConstrainedForeignId('archive_par_user_id');
            }

            foreach (['archive_le', 'archive_date_retour', 'archive_motif'] as $column) {
                if (Schema::hasColumn('inscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
