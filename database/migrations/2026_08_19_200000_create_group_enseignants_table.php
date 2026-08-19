<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint as B;
use Illuminate\Support\Facades\Schema;

/*
 * Teaching assignment history for a group ("affectation prof").
 *
 * A group keeps ONE active teacher at a time, but teachers change mid-course
 * (the group starts with Ahmed in September, he becomes unavailable in
 * October, Sara takes over). Before this table the change silently
 * overwrote groups.enseignant_id and the previous teacher disappeared —
 * making per-teacher payroll ("what did Ahmed actually teach, from when to
 * when?") impossible to answer.
 *
 * Each row is one assignment period: date_debut always set, date_fin NULL
 * while active and stamped when the teacher is replaced/removed. Exactly one
 * row per group may have statut = 'Actif'; groups.enseignant_id stays as a
 * denormalized mirror of that row so every existing query (séances filters,
 * groups list, GetCreneauFormOptions…) keeps working unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_enseignants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained('employees')->restrictOnDelete();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('statut', 20)->default('Actif'); // Actif / Archivé
            $table->text('motif')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();

            // PostgreSQL does not index FK columns automatically (§17).
            $table->index(['group_id', 'statut']);
            $table->index(['enseignant_id', 'date_debut']);
            $table->index('created_by');
        });

        // At most one active assignment per group — enforced by the database,
        // not just by the Domain action, so no concurrent request can leave a
        // group with two "current" teachers.
        DB::statement(
            'CREATE UNIQUE INDEX group_enseignants_one_actif_idx
             ON group_enseignants (group_id) WHERE statut = \'Actif\''
        );

        // Backfill: every group that already has a teacher gets its opening
        // assignment period, starting at the training start date (or the
        // group's creation date when none is set).
        DB::statement(
            "INSERT INTO group_enseignants
                (group_id, enseignant_id, date_debut, date_fin, statut, created_at, updated_at)
             SELECT id, enseignant_id,
                    COALESCE(date_debut_formation, created_at::date, CURRENT_DATE),
                    NULL, 'Actif', NOW(), NOW()
             FROM groups
             WHERE enseignant_id IS NOT NULL"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('group_enseignants');
    }
};
