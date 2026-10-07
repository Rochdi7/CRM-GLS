<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes de VIREMENT (07/10/2026) — un virement bancaire n'est plus saisi
 * comme un encaissement direct : il est DÉCLARÉ par l'employé de guichet,
 * puis VÉRIFIÉ par le comptable.
 *
 * Flux en deux temps, sur le modèle des remises de chèques et des dépenses :
 *  1. DEMANDE  — l'étudiant se présente avec la preuve de son virement ; le
 *                guichet saisit le payeur, la référence bancaire, le
 *                justificatif (média `justificatif`) et le frais réglé.
 *                AUCUN argent ne bouge : rien n'est encore vérifié ;
 *  2. DÉCISION — le comptable VALIDE (ValiderVirement : l'encaissement est
 *                créé via EnregistrerEncaissement, le compte « Virement » du
 *                centre est crédité, daté du jour de la DEMANDE) ou REFUSE
 *                avec un motif. La ligne est conservée dans les deux cas.
 *
 * `date_operation` est la date où l'étudiant s'est présenté au centre — la
 * date du paiement sur l'encaissement qui naît à la validation, jamais le
 * jour où le comptable a cliqué.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virements', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('inscription_id')->constrained('inscriptions')->restrictOnDelete();
            // Le frais réglé. nullOnDelete : une ligne de frais supprimée
            // laisse la demande lisible, et la validation la refuse alors.
            $table->foreignId('inscription_fee_id')->nullable()->constrained('inscription_fees')->nullOnDelete();
            // L'encaissement créé à la validation.
            $table->foreignId('encaissement_id')->nullable()->constrained('encaissements')->nullOnDelete();
            // Centre ACTIF au moment de la demande (repli : centre de
            // l'étudiant) — c'est le compte « Virement » de ce centre qui
            // sera crédité, jamais celui du contexte du comptable.
            $table->foreignId('etablissement_id')->constrained('etablissements')->restrictOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('nom_payeur', 150);
            $table->string('reference_virement', 100);
            $table->date('date_operation');
            // En attente de vérification / Validé / Refusé
            $table->string('statut', 40)->default('En attente de vérification');
            $table->text('note')->nullable();
            $table->text('motif_refus')->nullable();
            $table->foreignId('demande_par_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('decide_par_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('decide_le')->nullable();
            $table->timestamps();

            $table->index('student_id', 'virements_student_id_idx');
            $table->index('inscription_id', 'virements_inscription_id_idx');
            $table->index('inscription_fee_id', 'virements_inscription_fee_id_idx');
            $table->index('encaissement_id', 'virements_encaissement_id_idx');
            $table->index(['etablissement_id', 'statut'], 'virements_etablissement_statut_idx');
            $table->index('demande_par_id', 'virements_demande_par_id_idx');
            $table->index('decide_par_id', 'virements_decide_par_id_idx');
            $table->index('date_operation', 'virements_date_operation_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('virements');
    }
};
