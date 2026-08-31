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
            // What the reason may be used to cancel. A class session is not
            // cancelled for the reasons an enrollment is: « Malade », « jour
            // férié » or « Match maroc » explain why a séance did not take
            // place and say nothing about why a student left, while
            // « Non-paiement » or « Transfert d'établissement » are the
            // reverse. One shared catalogue offered both lists on both forms.
            // 'tous' keeps a reason valid everywhere (the seeded generic ones).
            $table->string('portee', 20)->default('tous');
            $table->string('statut', 20)->default('Actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motifs_annulation');
    }
};
