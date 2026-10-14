<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (14/10/2026) : les PAIEMENTS COCHÉS à la demande
 * de transfert d'un étudiant (`encaissements.id`, liste JSON).
 *
 * Avant, la validation emportait tout l'argent du dossier Actif ; désormais
 * le demandeur choisit paiement par paiement (année → inscription), et
 * `ValiderTransfertEtudiant` n'exécute que ce choix
 * (`Domain\Students\Support\SelectionPaiementsTransfert`). Nullable, AUCUN
 * backfill : NULL = demande antérieure à la colonne, validée avec la
 * sélection par défaut — exactement ce qu'elle promettait.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_transfers', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_transfers', 'encaissement_ids')) {
                $table->jsonb('encaissement_ids')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_transfers', function (Blueprint $table): void {
            if (Schema::hasColumn('student_transfers', 'encaissement_ids')) {
                $table->dropColumn('encaissement_ids');
            }
        });
    }
};
