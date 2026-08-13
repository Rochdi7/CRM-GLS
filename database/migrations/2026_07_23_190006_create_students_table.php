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
            $table->string('cin', 30)->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable(); // WhatsApp is GLS's dominant channel
            $table->string('email', 255)->nullable();
            $table->string('adresse', 255)->nullable();
            // validated against Student::NIVEAUX, not a FK (schema §5); widened to 20
            // to also carry the non-CEFR tracks (Arbeit / Studium / Ausbildung).
            $table->string('niveau', 20)->nullable();
            // Mutually exclusive by usage, both nullable (a plain CEFR level uses neither):
            // domaine → Arbeit / Ausbildung (Student::DOMAINES); examen_type → Studium (STK / DSH).
            $table->string('domaine', 60)->nullable();
            $table->string('examen_type', 10)->nullable();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->string('parent_nom', 100)->nullable();
            $table->string('parent_relation', 50)->nullable();
            $table->string('parent_sexe', 10)->nullable();
            $table->string('parent_cin', 30)->nullable();
            $table->string('parent_telephone', 20)->nullable();
            $table->string('parent_whatsapp', 20)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('etablissement_id', 'students_etablissement_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
