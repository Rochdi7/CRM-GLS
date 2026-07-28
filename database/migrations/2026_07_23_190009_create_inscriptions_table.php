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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};
