<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motifs_annulation', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 150);
            // System reasons (e.g. "Changement de groupe") are written by
            // application flows (ChangerGroupeInscription) — locked from
            // edit/delete like types_depenses.is_system rows.
            $table->boolean('is_system')->default(false);
            $table->string('statut', 20)->default('Actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motifs_annulation');
    }
};
