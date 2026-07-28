<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §13 — Expenses (cash outflows).
 * mots_cles is comma-separated free text by design (no tags table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('type_depense_id')->constrained('types_depenses')->restrictOnDelete();
            $table->foreignId('caisse_id')->constrained('caisses')->restrictOnDelete();
            $table->decimal('montant', 12, 2);
            $table->date('date_depense');
            $table->string('description', 255)->nullable();
            $table->string('mots_cles', 255)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('agent_id')->constrained('employees')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
