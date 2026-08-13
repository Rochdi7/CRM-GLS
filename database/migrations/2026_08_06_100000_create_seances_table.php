<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Attendance module — class sessions ("séances"). One row per group session
// on a given date; the per-student roll call lives in `presences`.
// etablissement_id / annee_scolaire_id are denormalized from the group so
// center scoping and year scoping work exactly like inscriptions.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->restrictOnDelete();
            // Links back to the weekly recurring slot this séance was
            // generated from — nullable, manually-created séances have none.
            $table->foreignId('creneau_id')->nullable()->constrained('creneaux')->nullOnDelete();
            $table->date('date_seance');
            $table->time('heure_debut')->nullable();
            $table->time('heure_fin')->nullable();
            $table->foreignId('enseignant_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('annee_scolaire_id')->nullable()->constrained('annees_scolaires')->nullOnDelete();
            $table->string('statut', 20)->default('Prévue'); // Prévue / Effectuée / Annulée
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            // PostgreSQL does not index FK columns automatically (§17) —
            // group_id is covered by the composite (list is filtered by group
            // and ordered by date), the rest get standalone indexes.
            $table->index(['group_id', 'date_seance']);
            $table->index('date_seance');
            $table->index('creneau_id');
            $table->index('enseignant_id');
            $table->index('etablissement_id');
            $table->index('annee_scolaire_id');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
