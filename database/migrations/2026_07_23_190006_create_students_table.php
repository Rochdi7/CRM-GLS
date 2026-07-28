<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// gls-crm-schema.md §5 — Students (parent/guardian contact inline, niveau is a plain VARCHAR by design)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('sexe', 10)->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable(); // WhatsApp is GLS's dominant channel
            $table->string('email', 255)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('niveau', 10)->nullable(); // validated against Student::NIVEAUX, not a FK (schema §5)
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->string('parent_nom', 100)->nullable();
            $table->string('parent_telephone', 20)->nullable();
            $table->string('parent_whatsapp', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
