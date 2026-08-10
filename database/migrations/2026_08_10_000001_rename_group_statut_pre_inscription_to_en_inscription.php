<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * The "Pré-inscription" group statut is renamed "En inscription" (matches the
 * reference CRM's tab wording). The create_groups_table migration was updated
 * in place (pre-production, allowed per CLAUDE.md §17) — this migration
 * converts databases that already ran the old default and their existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE groups ALTER COLUMN statut SET DEFAULT 'En inscription'");
        DB::table('groups')->where('statut', 'Pré-inscription')->update(['statut' => 'En inscription']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE groups ALTER COLUMN statut SET DEFAULT 'Pré-inscription'");
        DB::table('groups')->where('statut', 'En inscription')->update(['statut' => 'Pré-inscription']);
    }
};
