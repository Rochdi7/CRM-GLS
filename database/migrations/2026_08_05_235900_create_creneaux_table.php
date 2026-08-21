<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Emploi du temps — weekly recurring schedule slots ("créneaux"). A créneau
// is a template ("this group meets every Monday 10:00-12:00 in room X"),
// distinct from `seances` (one row per dated occurrence, used for
// attendance). GenererSeancesDepuisCreneau generates/syncs future "Prévue"
// séances from each créneau — see app/Domain/Attendance/Actions.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creneaux', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            // 1 = Lundi … 7 = Dimanche (ISO-8601 weekday, matches Carbon::dayOfWeekIso).
            $table->unsignedTinyInteger('jour_semaine');
            // Validity period of the slot. When a group's teacher changes the
            // outgoing teacher's emploi du temps must STOP rather than silently
            // transfer: each creneau is closed with date_fin = the changeover
            // date and its future "Prevue" seances are removed, keeping each
            // teacher's seances cleanly separated for per-teacher payroll.
            // date_debut NULL = "since always"; date_fin NULL = still running.
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->foreignId('enseignant_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('salle_id')->nullable()->constrained('salles')->nullOnDelete();
            $table->timestamps();

            // PostgreSQL does not index FK columns automatically (§17).
            $table->index(['group_id', 'jour_semaine']);
            $table->index('enseignant_id');
            $table->index('salle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creneaux');
    }
};
