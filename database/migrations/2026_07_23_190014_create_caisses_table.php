<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * gls-crm-schema.md §10 — Cash registers / tills.
 * `solde` is a stored, application-updated number (NOT computed from a ledger)
 * — deliberate simplicity trade-off; every encaissement/depense/remboursement/
 * transfer MUST update it in the same transaction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('caisses', function (Blueprint $table): void {
            $table->id();
            $table->string('nom', 100);
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('responsable_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('solde', 12, 2)->nullable()->default(0);
            $table->string('statut', 20)->default('Active');
            $table->timestamps();

            $table->index('etablissement_id', 'caisses_etablissement_id_idx');
            $table->index('responsable_employee_id', 'caisses_responsable_employee_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('caisses');
    }
};
