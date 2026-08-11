<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ICE (Identifiant Commun de l'Entreprise) du centre — imprimé sur les reçus.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etablissements', function (Blueprint $table): void {
            $table->string('ice', 30)->nullable()->after('adresse');
        });
    }

    public function down(): void
    {
        Schema::table('etablissements', function (Blueprint $table): void {
            $table->dropColumn('ice');
        });
    }
};
