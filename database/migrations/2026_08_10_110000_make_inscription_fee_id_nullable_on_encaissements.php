<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §11 flagged this exact trade-off: inscription_fee_id was
 * REQUIRED so that "true unallocated advances" would need an explicit,
 * documented migration rather than a silent change. This is that migration —
 * an "avance" is now a real Encaissement with inscription_fee_id = NULL:
 * money received from a student but not yet allocated to any fee, applied to
 * a fee later via a second Encaissement row (AppliquerAvance) that carries
 * applied_from_encaissement_id back to this one. Applying an avance never
 * touches caisses.solde again — the money already arrived when the avance
 * itself was recorded.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE encaissements ALTER COLUMN inscription_fee_id DROP NOT NULL');

        Schema::table('encaissements', function (Blueprint $table): void {
            $table->foreignId('applied_from_encaissement_id')->nullable()->after('inscription_fee_id')
                ->constrained('encaissements')->nullOnDelete();

            $table->index('applied_from_encaissement_id');
        });
    }

    public function down(): void
    {
        Schema::table('encaissements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('applied_from_encaissement_id');
        });

        DB::statement('ALTER TABLE encaissements ALTER COLUMN inscription_fee_id SET NOT NULL');
    }
};
