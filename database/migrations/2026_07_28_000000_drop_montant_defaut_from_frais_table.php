<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * The catalog no longer carries a default amount: fee amounts are always
 * decided per group (group_frais.montant) when the group is created — the
 * same fee is billed differently between centers, so a catalog-level
 * default is meaningless.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frais', function (Blueprint $table): void {
            $table->dropColumn('montant_defaut');
        });
    }

    public function down(): void
    {
        Schema::table('frais', function (Blueprint $table): void {
            $table->decimal('montant_defaut', 10, 2)->nullable();
        });
    }
};
