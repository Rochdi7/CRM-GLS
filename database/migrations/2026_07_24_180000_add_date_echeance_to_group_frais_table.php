<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Per-group due date for an assigned fee: different groups can bill the same
 * catalog fee with different échéances. Pre-fills the fee line's date_echeance
 * when enrolling a student into the group.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_frais', function (Blueprint $table): void {
            $table->date('date_echeance')->nullable()->after('montant');
        });
    }

    public function down(): void
    {
        Schema::table('group_frais', function (Blueprint $table): void {
            $table->dropColumn('date_echeance');
        });
    }
};
