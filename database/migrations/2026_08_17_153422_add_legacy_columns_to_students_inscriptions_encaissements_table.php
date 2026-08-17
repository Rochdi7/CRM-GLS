<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * students/inscriptions have a direct etablissement_id column, so their
     * legacy_ref only needs to be unique per centre. encaissements has no
     * etablissement_id column at all (it's reached only via student/
     * inscription) — its legacy_ref is scoped globally instead.
     */
    private const array TABLES_WITH_ETABLISSEMENT = ['students', 'inscriptions'];

    private const string ENCAISSEMENTS_TABLE = 'encaissements';

    public function up(): void
    {
        foreach ([...self::TABLES_WITH_ETABLISSEMENT, self::ENCAISSEMENTS_TABLE] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('legacy_ref', 50)->nullable()->after('reference');
                $table->string('legacy_source', 30)->nullable()->after('legacy_ref');
            });
        }

        foreach (self::TABLES_WITH_ETABLISSEMENT as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->unique(['etablissement_id', 'legacy_ref'], "{$table}_etab_legacy_ref_unique");
            });
        }

        Schema::table(self::ENCAISSEMENTS_TABLE, function (Blueprint $blueprint): void {
            $blueprint->unique('legacy_ref', 'encaissements_legacy_ref_unique');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES_WITH_ETABLISSEMENT as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropUnique("{$table}_etab_legacy_ref_unique");
                $blueprint->dropColumn(['legacy_ref', 'legacy_source']);
            });
        }

        Schema::table(self::ENCAISSEMENTS_TABLE, function (Blueprint $blueprint): void {
            $blueprint->dropUnique('encaissements_legacy_ref_unique');
            $blueprint->dropColumn(['legacy_ref', 'legacy_source']);
        });
    }
};
