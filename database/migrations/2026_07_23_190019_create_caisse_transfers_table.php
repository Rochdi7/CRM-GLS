<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §15 — Till-to-till transfers.
 * Highest fraud-risk operation in the system:
 * - requested_by ≠ validated_by (two different people, approval-gated)
 * - the four solde_* snapshots reconstruct balances at transfer time
 * - caisses.solde only changes at VALIDATION, never at request time
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisse_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('caisse_source_id')->constrained('caisses')->restrictOnDelete();
            $table->foreignId('caisse_destination_id')->constrained('caisses')->restrictOnDelete();
            $table->decimal('montant', 12, 2);
            $table->dateTime('date_transfert');
            // Centre ACTIF du caissier au moment de la DEMANDE (09/09/2026).
            // Une caisse n'a qu'UN centre de rattachement mais encaisse pour
            // plusieurs (§11) : sans cette colonne, la ventilation retombait sur
            // `caisses.etablissement_id` et imputait la sortie au mauvais centre
            // — 1 300 DH encaissés à Casablanca puis transférés en sortaient
            // comptablement à Kénitra, laissant Casablanca à 1 300 au lieu de 0.
            // Nullable : les transferts antérieurs n'ont pas la donnée et se
            // lisent avec un repli (jamais de backfill, §11).
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->decimal('solde_source_avant', 12, 2)->nullable();
            $table->decimal('solde_source_apres', 12, 2)->nullable();
            $table->decimal('solde_dest_avant', 12, 2)->nullable();
            $table->decimal('solde_dest_apres', 12, 2)->nullable();
            $table->string('statut', 20)->default('En attente'); // En attente / Validé / Annulé
            $table->text('note')->nullable();
            $table->foreignId('requested_by')->constrained('employees')->restrictOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->index('caisse_source_id', 'caisse_transfers_caisse_source_id_idx');
            $table->index('caisse_destination_id', 'caisse_transfers_caisse_destination_id_idx');
            $table->index('requested_by', 'caisse_transfers_requested_by_idx');
            $table->index('validated_by', 'caisse_transfers_validated_by_idx');
            $table->index('etablissement_id', 'caisse_transfers_etablissement_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisse_transfers');
    }
};
