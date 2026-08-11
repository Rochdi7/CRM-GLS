<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adresse postale du centre — imprimée en en-tête des reçus de paiement.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table): void {
            $table->string('adresse', 255)->nullable()->after('ville');
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table): void {
            $table->dropColumn('adresse');
        });
    }
};
