<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (audit 27/08/2026 R-04/DB-11): records WHO hid a
 * fee line so restoring a fee on a group never resurrects an exemption
 * granted by hand on one registration. Existing rows keep NULL (= legacy,
 * treated as group-hidden). Nothing is dropped or rewritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('inscription_fees', 'masque_origine')) {
            return;
        }

        Schema::table('inscription_fees', function (Blueprint $table): void {
            $table->string('masque_origine', 20)->nullable()->after('masque_le');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('inscription_fees', 'masque_origine')) {
            Schema::table('inscription_fees', fn (Blueprint $table) => $table->dropColumn('masque_origine'));
        }
    }
};
