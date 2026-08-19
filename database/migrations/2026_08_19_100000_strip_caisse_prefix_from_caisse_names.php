<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tills are named after their owning employee only — the list/journal screens
 * already carry a "Caisse" column header, so the legacy "Caisse — " prefix
 * (CaisseProvisioner) was pure duplication. Strip it from existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('caisses')
            ->where('nom', 'like', 'Caisse — %')
            ->update(['nom' => DB::raw("btrim(regexp_replace(nom, '^Caisse\s+—\s*', ''))")]);
    }

    public function down(): void
    {
        // Cosmetic rename only — nothing to restore.
    }
};
