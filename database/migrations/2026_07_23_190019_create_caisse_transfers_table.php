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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisse_transfers');
    }
};
