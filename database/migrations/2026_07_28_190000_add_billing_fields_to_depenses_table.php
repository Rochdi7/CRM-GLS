<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Expenses: billing fields from the legacy app's « Ajouter dépense » form.
 *
 *  - reference_facture : supplier invoice number (free text)
 *  - methode_paiement  : how it was paid — validated against
 *                        Depense::METHODES (same list as Encaissement)
 *  - group_id          : optional link to the class/group the expense
 *                        belongs to (groups are never deleted, schema §6)
 *
 * All nullable: legacy rows predate these fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table): void {
            $table->string('methode_paiement', 20)->nullable()->after('montant');
            $table->string('reference_facture', 100)->nullable()->after('date_depense');
            $table->foreignId('group_id')->nullable()->after('caisse_id')
                ->constrained('groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn(['methode_paiement', 'reference_facture']);
        });
    }
};
