<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * "Hide" a fee line instead of deleting it — the edit modal's trash icon no
 * longer hard-deletes (MettreAJourFraisInscription used to drop any row
 * omitted from the submitted payload). A hidden fee keeps its row and its
 * payment history intact (money records are append-only, CLAUDE.md §11) and
 * can be restored from the "Frais masqués" list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscription_fees', function (Blueprint $table): void {
            $table->timestamp('masque_le')->nullable()->after('statut');
        });
    }

    public function down(): void
    {
        Schema::table('inscription_fees', function (Blueprint $table): void {
            $table->dropColumn('masque_le');
        });
    }
};
