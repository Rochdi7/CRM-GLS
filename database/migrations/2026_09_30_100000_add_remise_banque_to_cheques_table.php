<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only, additive (30/09/2026) : le parcours bancaire d'un chèque.
 *
 * `cheques` :
 *  - `date_remise` / `depose_par_id` — la REMISE À LA BANQUE : la date et
 *    qui l'a faite (le reçu de dépôt est un média, collection
 *    `justificatif_depot`) ;
 *  - `depot_valide_le` / `depot_valide_par_id` — la DÉCISION du comptable
 *    (ValiderRemiseCheque) : « Encaissé » = la banque a accepté le chèque.
 *    Aucun argent ne bouge, il reste au compte « Chèque » du centre ;
 *  - `restitution_encaissement_id` — le paiement (espèces / TPE / virement)
 *    qui a REMPLACÉ un chèque de garantie rendu à son propriétaire.
 *
 * Aucune ligne existante n'est réécrite : toutes les colonnes sont NULL
 * pour l'historique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table): void {
            if (! Schema::hasColumn('cheques', 'date_remise')) {
                $table->date('date_remise')->nullable();
            }
            if (! Schema::hasColumn('cheques', 'depose_par_id')) {
                $table->foreignId('depose_par_id')->nullable()->constrained('employees')->restrictOnDelete();
            }
            if (! Schema::hasColumn('cheques', 'depot_valide_le')) {
                $table->timestamp('depot_valide_le')->nullable();
            }
            if (! Schema::hasColumn('cheques', 'depot_valide_par_id')) {
                $table->foreignId('depot_valide_par_id')->nullable()->constrained('employees')->restrictOnDelete();
            }
            if (! Schema::hasColumn('cheques', 'restitution_encaissement_id')) {
                $table->foreignId('restitution_encaissement_id')->nullable()->constrained('encaissements')->restrictOnDelete();
                $table->index('restitution_encaissement_id', 'cheques_restitution_encaissement_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table): void {
            foreach (['depose_par_id', 'depot_valide_par_id', 'restitution_encaissement_id'] as $fk) {
                if (Schema::hasColumn('cheques', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
            foreach (['date_remise', 'depot_valide_le'] as $col) {
                if (Schema::hasColumn('cheques', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
