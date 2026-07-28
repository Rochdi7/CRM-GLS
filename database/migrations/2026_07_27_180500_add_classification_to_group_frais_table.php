<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Per-group classification of an assigned fee: each fee line can be tagged
 * with the CEFR niveau it belongs to (e.g. "Frais d'inscription B2" → B2.1).
 * Plain VARCHAR validated against Group::NIVEAUX (schema §5 — no lookup table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_frais', function (Blueprint $table): void {
            $table->string('classification', 10)->nullable()->after('date_echeance');
        });
    }

    public function down(): void
    {
        Schema::table('group_frais', function (Blueprint $table): void {
            $table->dropColumn('classification');
        });
    }
};
