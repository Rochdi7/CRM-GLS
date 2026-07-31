<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 12 database audit: PostgreSQL does not auto-index FK columns
 * (CLAUDE.md §17). Two FK columns are on real query paths without a
 * leading index:
 *
 * - group_frais.frais_id — FraisController@destroy's loadCount('groups')
 *   filters the pivot by frais_id alone; the existing unique index leads
 *   with group_id so it can't serve this lookup.
 * - groups_historique.group_id — Group::historique() (HasOne) filters by
 *   group_id; every other FK on this table is indexed, this one was
 *   missed.
 *
 * All other FK columns repo-wide are covered (verified via pg_constraint
 * vs pg_index; role_has_permissions.role_id is second in Spatie's own
 * composite PK — vendor-standard, left as shipped).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_frais', function (Blueprint $table): void {
            $table->index('frais_id', 'group_frais_frais_id_idx');
        });

        Schema::table('groups_historique', function (Blueprint $table): void {
            $table->index('group_id', 'groups_historique_group_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('group_frais', function (Blueprint $table): void {
            $table->dropIndex('group_frais_frais_id_idx');
        });

        Schema::table('groups_historique', function (Blueprint $table): void {
            $table->dropIndex('groups_historique_group_id_idx');
        });
    }
};
