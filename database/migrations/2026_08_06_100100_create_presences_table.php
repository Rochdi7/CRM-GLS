<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Attendance module — per-student roll call for a séance. One row per
// (séance, student); deleting a séance removes its roll call with it.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seance_id')->constrained('seances')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->string('statut', 20); // Présent / Absent / Retard / Justifié
            $table->string('note', 255)->nullable();
            $table->timestamps();

            // The unique pair doubles as the seance_id index (§17); student_id
            // needs its own for per-student attendance lookups.
            $table->unique(['seance_id', 'student_id']);
            $table->index('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presences');
    }
};
