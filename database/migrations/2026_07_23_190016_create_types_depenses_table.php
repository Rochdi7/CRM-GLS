<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §12 — Expense type catalog.
 * is_system = true rows are seeded once (TypeDepenseSeeder) and are locked
 * from admin editing — other code (teacher payroll, till transfers) depends
 * on their existence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('types_depenses', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 100);
            $table->boolean('is_system')->default(false);
            $table->string('statut', 20)->default('Actif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('types_depenses');
    }
};
