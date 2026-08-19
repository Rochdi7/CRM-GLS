<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Employees may work in SEVERAL centers (and must have at least one).
 *
 * Supersedes the single `employees.etablissement_id` column, which stays in
 * place as the "primary" center (first assigned) so existing joins, the
 * Caisse provisioner and every `etablissement_id` filter keep working —
 * this pivot is the source of truth for ACCESS (CenterAccessService), the
 * column is the source of truth for "where this employee is based".
 *
 * Backfill: every existing employee is attached to its current center; those
 * with a NULL center are attached to the lowest-id établissement so the
 * "at least one center" rule holds for all pre-existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_etablissement', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('etablissement_id')->constrained('etablissements')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'etablissement_id'], 'employee_etablissement_unique');
            // PostgreSQL does not index the referencing side of an FK (CLAUDE.md §17).
            // employee_id is already covered by the unique composite above.
            $table->index('etablissement_id', 'employee_etablissement_etab_idx');
        });

        $fallbackCenterId = DB::table('etablissements')->orderBy('id')->value('id');

        DB::table('employees')->orderBy('id')->chunkById(500, function ($employees) use ($fallbackCenterId): void {
            $rows = [];
            $now = now();

            foreach ($employees as $employee) {
                $centerId = $employee->etablissement_id ?? $fallbackCenterId;

                if ($centerId === null) {
                    continue; // No établissement exists at all — nothing to attach.
                }

                $rows[] = [
                    'employee_id' => $employee->id,
                    'etablissement_id' => $centerId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Keep the primary column in sync for previously NULL rows.
                if ($employee->etablissement_id === null) {
                    DB::table('employees')->where('id', $employee->id)
                        ->update(['etablissement_id' => $centerId]);
                }
            }

            if ($rows !== []) {
                DB::table('employee_etablissement')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_etablissement');
    }
};
