<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * A créneau (weekly recurring slot) now carries the period it is valid for.
 *
 * When a group's teacher changes, the outgoing teacher's emploi du temps
 * must STOP rather than silently transfer to the new teacher: each of the
 * group's créneaux is closed with date_fin = the changeover date, its future
 * "Prévue" séances are removed, and the user is asked to build a fresh
 * emploi du temps for the incoming teacher. That keeps each teacher's
 * séances cleanly separated for per-teacher payroll.
 *
 * date_debut is nullable-with-default-null = "since always" (existing rows);
 * date_fin NULL = still running.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('creneaux', function (Blueprint $table): void {
            $table->date('date_debut')->nullable()->after('jour_semaine');
            $table->date('date_fin')->nullable()->after('date_debut');
        });
    }

    public function down(): void
    {
        Schema::table('creneaux', function (Blueprint $table): void {
            $table->dropColumn(['date_debut', 'date_fin']);
        });
    }
};
