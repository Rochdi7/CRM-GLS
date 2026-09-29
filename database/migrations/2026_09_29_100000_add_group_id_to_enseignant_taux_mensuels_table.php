<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (29/09/2026) : un montant win-win peut désormais
 * viser UN groupe de l'enseignant.
 *
 * `group_id` NULL = « Tous les groupes » (le comportement d'avant, toutes les
 * lignes existantes le gardent — aucun backfill). Une ligne AVEC groupe prime
 * sur la ligne générale du même mois (`ConfigurationPaieEnseignant`).
 *
 * L'unicité passe de (employee_id, mois) à (employee_id, group_id, mois)
 * NULLS NOT DISTINCT : un seul montant général par mois, un seul par groupe
 * et par mois (PostgreSQL 15+, la cible est 16+).
 *
 * `cascadeOnDelete` : le montant d'un groupe supprimé ne décrit plus rien —
 * le rabattre sur « tous les groupes » (nullOnDelete) changerait en silence
 * la paie de tous les autres groupes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('enseignant_taux_mensuels', 'group_id')) {
            Schema::table('enseignant_taux_mensuels', function (Blueprint $table): void {
                $table->foreignId('group_id')->nullable()->after('employee_id')
                    ->constrained('groups')->cascadeOnDelete();
                $table->index('group_id');
            });
        }

        DB::statement('ALTER TABLE enseignant_taux_mensuels DROP CONSTRAINT IF EXISTS enseignant_taux_mensuels_unique');
        DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS enseignant_taux_mensuels_groupe_unique
            ON enseignant_taux_mensuels (employee_id, group_id, mois) NULLS NOT DISTINCT');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS enseignant_taux_mensuels_groupe_unique');

        if (Schema::hasColumn('enseignant_taux_mensuels', 'group_id')) {
            DB::table('enseignant_taux_mensuels')->whereNotNull('group_id')->delete();

            Schema::table('enseignant_taux_mensuels', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('group_id');
            });
        }

        DB::statement('ALTER TABLE enseignant_taux_mensuels
            ADD CONSTRAINT enseignant_taux_mensuels_unique UNIQUE (employee_id, mois)');
    }
};
