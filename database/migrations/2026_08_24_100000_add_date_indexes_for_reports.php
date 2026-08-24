<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Date-column indexes for the hot report paths (24/08/2026 performance pass).
 *
 * PostgreSQL never indexes a column on its own (CLAUDE.md §17). These three
 * date columns are the leading predicate of the dashboard aggregates
 * (GetAnnualFraisSummary, GetDashboardStats), the Encaissements list's
 * default ordering + date filters, and the Recouvrements overdue scan —
 * every one of them was a sequential scan of the whole table per request.
 * `encaissements.date_paiement` was only covered as the SECOND column of
 * (caisse_id, date_paiement), which cannot serve a query that does not
 * filter by till.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encaissements', function (Blueprint $table): void {
            $table->index('date_paiement', 'encaissements_date_paiement_idx');
        });

        Schema::table('inscription_fees', function (Blueprint $table): void {
            $table->index('date_echeance', 'inscription_fees_date_echeance_idx');
        });

        Schema::table('depenses', function (Blueprint $table): void {
            $table->index('date_depense', 'depenses_date_depense_idx');
        });
    }

    public function down(): void
    {
        Schema::table('encaissements', function (Blueprint $table): void {
            $table->dropIndex('encaissements_date_paiement_idx');
        });

        Schema::table('inscription_fees', function (Blueprint $table): void {
            $table->dropIndex('inscription_fees_date_echeance_idx');
        });

        Schema::table('depenses', function (Blueprint $table): void {
            $table->dropIndex('depenses_date_depense_idx');
        });
    }
};
