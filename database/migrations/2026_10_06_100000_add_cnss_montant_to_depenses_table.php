<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (06/10/2026) : la cotisation CNSS RETENUE sur un
 * « Paiement prof » (`Domain\Payroll\Support\CotisationCnss`).
 *
 * Nullable, AUCUN backfill : NULL = aucune retenue (dépense ordinaire, ou
 * paiement prof sans CNSS). Le montant retenu est STOCKÉ plutôt qu'un
 * drapeau, pour que l'historique reste exact si la cotisation change un
 * jour. `montant` reste le NET versé — la seule valeur que la caisse débite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table): void {
            if (! Schema::hasColumn('depenses', 'cnss_montant')) {
                $table->decimal('cnss_montant', 12, 2)->nullable()->after('montant');
            }
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table): void {
            if (Schema::hasColumn('depenses', 'cnss_montant')) {
                $table->dropColumn('cnss_montant');
            }
        });
    }
};
