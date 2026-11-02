<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes de REMBOURSEMENT (02/11/2026) — un remboursement n'est plus saisi
 * comme une sortie de caisse immédiate quand « Validation des
 * remboursements » est active (AppSettings::REFUND_APPROVAL, ON par défaut).
 *
 * Flux en deux temps, sur le modèle des virements :
 *  1. DEMANDE  — l'employé saisit l'étudiant, le paiement remboursé (ou
 *                aucun), le montant et le motif. AUCUN argent ne bouge.
 *  2. DÉCISION — un directeur (dans ses centres affectés) ou un super-admin
 *                APPROUVE : le remboursement est alors créé par
 *                EnregistrerRemboursement, qui débite la caisse — ou REFUSE
 *                avec un motif. La ligne est conservée dans les deux cas.
 *
 * `methode` n'est connue qu'à l'approbation : Espèces par défaut (la caisse
 * PHYSIQUE de l'employé qui a fait la demande), TPE ou Virement au choix du
 * super-admin seul (le compte TPE / Virement du centre de la demande).
 *
 * Une table À PART, jamais un statut sur `remboursements` : une quarantaine
 * de lectures (journal, payé net, avances, relevés, auditeur de cohérence)
 * comptent chaque ligne `remboursements` comme de l'argent sorti. Une
 * demande en attente n'en est pas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_remboursement', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('beneficiaire_id')->constrained('students')->restrictOnDelete();
            // Le paiement remboursé. nullOnDelete : un paiement supprimé
            // laisse la demande lisible — `encaissement_reference` garde la
            // trace, et l'approbation refuse alors au lieu de rembourser
            // « sans paiement ».
            $table->foreignId('encaissement_id')->nullable()->constrained('encaissements')->nullOnDelete();
            $table->string('encaissement_reference', 30)->nullable();
            // Centre du remboursement, même règle qu'EnregistrerRemboursement :
            // celui du paiement d'origine, sinon celui de l'étudiant. C'est
            // lui qui décide quel directeur voit et approuve la demande, et
            // quel compte TPE / Virement est débité.
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('motif', 255)->nullable();
            $table->text('note')->nullable();
            // En attente / Approuvée / Refusée
            $table->string('statut', 20)->default('En attente');
            // Choisie à l'approbation (Espèces / TPE / Virement).
            $table->string('methode', 20)->nullable();
            $table->foreignId('remboursement_id')->nullable()->constrained('remboursements')->nullOnDelete();
            $table->text('motif_refus')->nullable();
            $table->foreignId('demande_par_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('decide_par_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('decide_le')->nullable();
            $table->timestamps();

            $table->index(['etablissement_id', 'statut'], 'demandes_rmb_etablissement_statut_idx');
            $table->index('beneficiaire_id', 'demandes_rmb_beneficiaire_id_idx');
            $table->index(['encaissement_id', 'statut'], 'demandes_rmb_encaissement_statut_idx');
            $table->index('remboursement_id', 'demandes_rmb_remboursement_id_idx');
            $table->index('demande_par_id', 'demandes_rmb_demande_par_id_idx');
            $table->index('decide_par_id', 'demandes_rmb_decide_par_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_remboursement');
    }
};
