<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// gls-crm-schema.md §8 — Enrollments (Student × Group with its own lifecycle)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            // Legacy import provenance — unique per centre (see students).
            $table->string('legacy_ref', 50)->nullable();
            $table->string('legacy_source', 30)->nullable();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('group_id')->constrained('groups')->restrictOnDelete();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
            $table->string('statut', 30)->default('Active'); // Active / Expirée / Archivée / Annulée
            $table->date('date_inscription');
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->decimal('montant_total', 10, 2)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            $table->index(['annee_scolaire_id', 'statut'], 'inscriptions_annee_statut_idx');
            $table->index('student_id', 'inscriptions_student_id_idx');
            $table->index('group_id', 'inscriptions_group_id_idx');
            $table->index('etablissement_id', 'inscriptions_etablissement_id_idx');
            $table->index('created_by', 'inscriptions_created_by_idx');
            $table->unique(['etablissement_id', 'legacy_ref'], 'inscriptions_etab_legacy_ref_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
