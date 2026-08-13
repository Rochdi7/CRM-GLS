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
            $table->string('methode_paiement', 20)->nullable();
            $table->date('date_depense');
            $table->string('reference_facture', 100)->nullable();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('description', 255)->nullable();
            $table->string('mots_cles', 255)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('agent_id')->constrained('employees')->restrictOnDelete();
            $table->timestamps();

            $table->index(['caisse_id', 'date_depense'], 'depenses_caisse_date_idx');
            $table->index('type_depense_id', 'depenses_type_depense_id_idx');
            $table->index('agent_id', 'depenses_agent_id_idx');
            $table->index('group_id', 'depenses_group_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
