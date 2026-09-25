<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demande de transfert d'un étudiant d'un CENTRE vers un AUTRE (25/09/2026).
 *
 * Flux en deux temps, sur le modèle des transferts de caisse :
 *  1. DEMANDE  — le front office d'un centre désigne le centre cible ET le
 *                groupe d'affectation dans ce centre ; rien ne bouge ;
 *  2. DÉCISION — un super-admin valide (ValiderTransfertEtudiant : copie de
 *                la fiche, clôture « Transférée » des dossiers du centre
 *                source, nouveau dossier Actif dans le groupe cible, argent
 *                déplacé) ou refuse avec un motif.
 *
 * La ligne porte tout ce qui a été décidé : le nouveau `students.id`, la
 * nouvelle inscription et le montant emporté — pour que la fiche source et
 * la fiche cible se renvoient l'une à l'autre sans rien redériver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            // La fiche SOURCE (celle du centre d'origine, conservée « Transféré »).
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            // La COPIE créée dans le centre cible à la validation.
            $table->foreignId('nouveau_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('etablissement_source_id')->constrained('etablissements')->restrictOnDelete();
            $table->foreignId('etablissement_cible_id')->constrained('etablissements')->restrictOnDelete();
            // Groupe d'affectation dans le centre cible — OBLIGATOIRE à la
            // demande : un transfert sans groupe laisserait l'étudiant sans
            // dossier à l'arrivée. nullOnDelete : la validation refuse alors.
            $table->foreignId('group_cible_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('nouvelle_inscription_id')->nullable()->constrained('inscriptions')->nullOnDelete();
            $table->string('statut', 20)->default('En attente'); // En attente / Validé / Refusé / Annulé
            $table->text('motif');
            $table->text('motif_decision')->nullable();
            $table->decimal('montant_transfere', 12, 2)->nullable();
            $table->foreignId('requested_by')->constrained('employees')->restrictOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();

            $table->index('student_id', 'student_transfers_student_id_idx');
            $table->index('nouveau_student_id', 'student_transfers_nouveau_student_id_idx');
            $table->index('etablissement_source_id', 'student_transfers_source_idx');
            $table->index('etablissement_cible_id', 'student_transfers_cible_idx');
            $table->index('group_cible_id', 'student_transfers_group_cible_idx');
            $table->index('statut', 'student_transfers_statut_idx');
            $table->index('requested_by', 'student_transfers_requested_by_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_transfers');
    }
};
