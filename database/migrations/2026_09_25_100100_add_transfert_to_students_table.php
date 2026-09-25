<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (25/09/2026) : transfert d'un étudiant entre centres.
 *
 * Une fiche étudiant appartient à UN centre (`etablissement_id`, `legacy_ref`
 * unique par centre). Un transfert ne DÉPLACE donc pas la fiche : le centre
 * d'origine garde la sienne — avec ses présences et son historique — passée
 * au statut « Transféré », et le centre d'arrivée reçoit une COPIE, Active.
 * Les deux colonnes de lien font que chaque fiche pointe vers l'autre.
 *
 * `statut` est un VARCHAR validé contre Student::STATUTS (même règle que tous
 * les autres statuts, gls-crm-schema.md). Défaut « Actif » : toutes les
 * fiches existantes le sont.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (! Schema::hasColumn('students', 'statut')) {
                $table->string('statut', 20)->default('Actif')->after('etablissement_id');
                $table->index('statut', 'students_statut_idx');
            }

            if (! Schema::hasColumn('students', 'transfere_vers_student_id')) {
                $table->foreignId('transfere_vers_student_id')
                    ->nullable()
                    ->after('statut')
                    ->constrained('students')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('students', 'transfere_depuis_student_id')) {
                $table->foreignId('transfere_depuis_student_id')
                    ->nullable()
                    ->after('transfere_vers_student_id')
                    ->constrained('students')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            if (Schema::hasColumn('students', 'transfere_depuis_student_id')) {
                $table->dropConstrainedForeignId('transfere_depuis_student_id');
            }

            if (Schema::hasColumn('students', 'transfere_vers_student_id')) {
                $table->dropConstrainedForeignId('transfere_vers_student_id');
            }

            if (Schema::hasColumn('students', 'statut')) {
                $table->dropIndex('students_statut_idx');
                $table->dropColumn('statut');
            }
        });
    }
};
