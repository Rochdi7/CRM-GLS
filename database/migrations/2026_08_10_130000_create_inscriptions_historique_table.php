<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * "Changement de groupe" snapshot — mirrors groups_historique's pattern
 * (gls-crm-schema.md §7): columns are COPIED at transfer time so the
 * snapshot reflects what was true when the student left the old group,
 * even if the (still-live, never-deleted) inscription row or its group are
 * edited later. Rows are inserted exclusively by
 * App\Domain\Registrations\Actions\ChangerGroupeInscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions_historique', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inscription_id')->constrained('inscriptions')->cascadeOnDelete();
            $table->foreignId('new_inscription_id')->nullable()->constrained('inscriptions')->nullOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->date('date_fin');
            $table->text('note')->nullable();
            $table->dateTime('archived_at');
            $table->foreignId('archived_by')->nullable()->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions_historique');
    }
};
