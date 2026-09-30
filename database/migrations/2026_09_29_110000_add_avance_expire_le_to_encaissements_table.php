<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (29/09/2026) : une avance EXPIRE.
 *
 * `avance_expire_le` est la date à partir de laquelle l'argent non affecté
 * d'un encaissement ne peut plus être APPLIQUÉ à un frais
 * (`Domain\Payments\Support\ValiditeAvance`, 14 jours).
 *
 * NULL = ligne antérieure à la colonne (ou insérée hors Eloquent) : la règle
 * se lit alors `date_paiement + 14 jours`, à la LECTURE — aucun backfill,
 * aucune ligne de production n'est réécrite par ce déploiement. La colonne
 * n'est renseignée que lorsqu'une ligne NAÎT avance ou le REDEVIENT
 * (détachement d'un frais), et par une prolongation explicite.
 *
 * Aucun montant, aucune caisse, aucun statut ne bouge : la colonne ne décide
 * que de ce qui est encore APPLICABLE.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('encaissements', 'avance_expire_le')) {
            Schema::table('encaissements', function (Blueprint $table): void {
                $table->date('avance_expire_le')->nullable()->after('date_paiement');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('encaissements', 'avance_expire_le')) {
            Schema::table('encaissements', function (Blueprint $table): void {
                $table->dropColumn('avance_expire_le');
            });
        }
    }
};
