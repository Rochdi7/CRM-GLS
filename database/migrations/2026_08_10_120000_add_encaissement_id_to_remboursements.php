<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Links a refund back to the specific payment it's refunding — the
 * Remboursement form now lists a selected student's payments so staff pick
 * one instead of typing a bare amount blind. Nullable: a refund unrelated to
 * any tracked payment (e.g. before Encaissements existed, or a goodwill
 * refund) is still allowed, matching the flexible montant that already had
 * no maximum-amount check (docs/phase-10-finance-audit.md §2.6 Q1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('remboursements', function (Blueprint $table): void {
            $table->foreignId('encaissement_id')->nullable()->after('beneficiaire_id')
                ->constrained('encaissements')->nullOnDelete();

            $table->index('encaissement_id');
        });
    }

    public function down(): void
    {
        Schema::table('remboursements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('encaissement_id');
        });
    }
};
