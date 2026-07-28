<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Per-line discount on an inscription fee (pic 3): the initial amount, a
 * percentage OR fixed discount, an optional note, and the resulting final
 * amount stored in the existing `montant` column.
 *   montant = montant_initial − remise (pct applied first, else fixed DH)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscription_fees', function (Blueprint $table): void {
            $table->decimal('montant_initial', 10, 2)->nullable()->after('nom');
            $table->decimal('remise_pct', 5, 2)->nullable()->after('montant_initial');
            $table->decimal('remise_montant', 10, 2)->nullable()->after('remise_pct');
            $table->foreignId('frais_id')->nullable()->after('remise_montant')->constrained('frais')->nullOnDelete();
            $table->string('note', 255)->nullable()->after('date_echeance');
        });
    }

    public function down(): void
    {
        Schema::table('inscription_fees', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('frais_id');
            $table->dropColumn(['montant_initial', 'remise_pct', 'remise_montant', 'note']);
        });
    }
};
