<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (19/10/2026) : statut « Archivé » d'un étudiant.
 *
 * Un étudiant qui INTERROMPT ses cours mais compte revenir (un mois, un
 * trimestre…) n'est ni transféré ni supprimé : sa fiche passe « Archivé »
 * avec un MOTIF et une DATE DE RETOUR prévue, sort de la liste courante
 * (onglet « Étudiants archivés ») et se RÉACTIVE d'un clic à son retour.
 * `statut` reste un VARCHAR validé contre Student::STATUTS. Aucune
 * inscription ni aucun argent ne bouge — c'est une étiquette sur la fiche.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (! Schema::hasColumn('students', 'archive_motif')) {
                $table->text('archive_motif')->nullable()->after('transfere_depuis_student_id');
            }

            if (! Schema::hasColumn('students', 'archive_date_retour')) {
                $table->date('archive_date_retour')->nullable()->after('archive_motif');
            }

            if (! Schema::hasColumn('students', 'archive_le')) {
                $table->timestamp('archive_le')->nullable()->after('archive_date_retour');
            }

            if (! Schema::hasColumn('students', 'archive_par_user_id')) {
                $table->foreignId('archive_par_user_id')
                    ->nullable()
                    ->after('archive_le')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
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
};
