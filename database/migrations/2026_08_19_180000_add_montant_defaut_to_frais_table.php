<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives the Frais catalog a default amount.
 *
 * Until now `frais` held only a name, so every group_frais pivot line was
 * created at 0.00 and had to be priced by hand for every group. That is
 * also why the "Enregistrer un paiement" modal listed no fees at all: the
 * unpaid-fees query filters on `reste = montant - payé > 0`, and a fee
 * worth 0.00 can never satisfy it.
 *
 * The catalog amount is only ever a DEFAULT — group_frais.montant stays
 * the authority for what a given group actually charges, so a group can
 * still override it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frais', function (Blueprint $table): void {
            $table->decimal('montant_defaut', 10, 2)->default(0)->after('nom');
        });
    }

    public function down(): void
    {
        Schema::table('frais', function (Blueprint $table): void {
            $table->dropColumn('montant_defaut');
        });
    }
};
