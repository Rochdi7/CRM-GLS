<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tracks physical return of a rejected chèque to its owner — off-ledger,
 * like the rest of the cheques table (§ create_cheques_table migration).
 * retourne_par_id is the employee who physically handed the chèque back;
 * agent_id (already on the table) remains "who originally received it".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques', function (Blueprint $table): void {
            $table->timestamp('retourne_le')->nullable()->after('statut');
            $table->foreignId('retourne_par_id')->nullable()->after('retourne_le')
                ->constrained('employees')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cheques', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('retourne_par_id');
            $table->dropColumn('retourne_le');
        });
    }
};
