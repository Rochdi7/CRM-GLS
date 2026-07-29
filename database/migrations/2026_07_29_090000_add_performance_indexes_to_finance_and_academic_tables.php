<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes justified by real query patterns (see PERFORMANCE_AUDIT.md
 * §6). `foreignId()->constrained()` auto-indexes single FK columns on MySQL
 * (InnoDB) but NOT on SQLite — these composites add the multi-column coverage
 * neither engine gets for free, and give local SQLite dev its first real
 * secondary indexes so it behaves like production.
 *
 * No existing index is touched or dropped; no data is modified.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Till journal + list filters + SUM aggregates (caisse_id + date range).
        Schema::table('encaissements', function (Blueprint $table): void {
            $table->index(['caisse_id', 'date_paiement'], 'encaissements_caisse_date_idx');
        });

        Schema::table('depenses', function (Blueprint $table): void {
            $table->index(['caisse_id', 'date_depense'], 'depenses_caisse_date_idx');
        });

        Schema::table('remboursements', function (Blueprint $table): void {
            $table->index(['caisse_id', 'date_remboursement'], 'remboursements_caisse_date_idx');
        });

        // Year-scoped list + statut filter + dashboard statut counts.
        Schema::table('inscriptions', function (Blueprint $table): void {
            $table->index(['annee_scolaire_id', 'statut'], 'inscriptions_annee_statut_idx');
        });

        // Full-table ORDER BY archived_at DESC pagination.
        Schema::table('groups_historique', function (Blueprint $table): void {
            $table->index('archived_at', 'groups_historique_archived_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('encaissements', function (Blueprint $table): void {
            $table->dropIndex('encaissements_caisse_date_idx');
        });

        Schema::table('depenses', function (Blueprint $table): void {
            $table->dropIndex('depenses_caisse_date_idx');
        });

        Schema::table('remboursements', function (Blueprint $table): void {
            $table->dropIndex('remboursements_caisse_date_idx');
        });

        Schema::table('inscriptions', function (Blueprint $table): void {
            $table->dropIndex('inscriptions_annee_statut_idx');
        });

        Schema::table('groups_historique', function (Blueprint $table): void {
            $table->dropIndex('groups_historique_archived_at_idx');
        });
    }
};
