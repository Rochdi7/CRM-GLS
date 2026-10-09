<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (08/10/2026) : les AJUSTEMENTS MANUELS du calcul
 * d'un « Paiement prof » (`student_id => montant imposé`), tels que
 * l'opérateur les a saisis dans « Calcul paiement prof » avant d'enregistrer.
 *
 * Ils n'étaient qu'un état React : « Modifier le calcul » rouvrait le calcul
 * BRUT et l'opérateur retapait chaque correction. Nullable, AUCUN backfill :
 * NULL = aucun ajustement (dépense ordinaire, ou calcul accepté tel quel).
 * `montant` reste le NET versé — seule valeur débitée — et les ajustements
 * n'en sont que l'explication, jamais une source recalculée à la lecture.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table): void {
            if (! Schema::hasColumn('depenses', 'calcul_ajustements')) {
                $table->jsonb('calcul_ajustements')->nullable()->after('cnss_montant');
            }
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table): void {
            if (Schema::hasColumn('depenses', 'calcul_ajustements')) {
                $table->dropColumn('calcul_ajustements');
            }
        });
    }
};
