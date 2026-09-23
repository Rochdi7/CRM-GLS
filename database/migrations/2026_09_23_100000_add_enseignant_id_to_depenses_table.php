<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (23/09/2026) : QUEL enseignant un « Paiement prof »
 * a payé.
 *
 * Jusqu'ici la ligne ne portait que `group_id` (le groupe) et `agent_id` (la
 * caissière qui a saisi). Le prof payé se DÉDUISAIT du groupe — faux dès que
 * le groupe change d'enseignant ou qu'un remplaçant est payé : l'historique
 * de compte d'un prof afficherait alors l'argent d'un autre.
 *
 * Nullable, AUCUN backfill (§11 : on ne fige pas une supposition dans un
 * enregistrement monétaire). Les lignes antérieures se lisent avec un repli
 * sur le prof ACTUEL du groupe, SIGNALÉ à l'écran comme « déduit du groupe »
 * (`GetHistoriquePaiementsEnseignant`). Une dépense ordinaire garde NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table): void {
            if (! Schema::hasColumn('depenses', 'enseignant_id')) {
                $table->foreignId('enseignant_id')
                    ->nullable()
                    ->after('group_id')
                    ->constrained('employees')
                    ->nullOnDelete();
                // Lu par l'historique du prof : « ses paiements, les plus
                // récents d'abord ».
                $table->index(['enseignant_id', 'date_depense'], 'depenses_enseignant_date_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table): void {
            if (Schema::hasColumn('depenses', 'enseignant_id')) {
                $table->dropIndex('depenses_enseignant_date_idx');
                $table->dropConstrainedForeignId('enseignant_id');
            }
        });
    }
};
