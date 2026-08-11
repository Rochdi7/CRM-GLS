<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Chèques in hand — an OFF-LEDGER inventory of physical checks received
 * (garantie or à déposer), tracked from reception to deposit/clearance.
 * A Cheque row NEVER touches caisses.solde by itself: money only enters a
 * till when the chèque is used to pay (an Encaissement row created via
 * EnregistrerEncaissement with cheque_id linking back here). "Reste" is
 * derived: montant - sum(encaissements.montant where cheque_id = id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 20)->unique();
            // Étudiant | Parents | Autre — who physically wrote the chèque.
            $table->string('source', 20);
            // Set when source = Étudiant (searchable picker); NULL otherwise.
            $table->foreignId('student_id')->nullable()->constrained('students')->restrictOnDelete();
            // Free-text owner name when source = Parents/Autre.
            $table->string('proprietaire_nom', 150)->nullable();
            $table->string('numero_cheque', 50);
            $table->decimal('montant', 12, 2);
            // Bank name as free text, consistent with encaissements.banque
            // (suggested from the banques catalog, not FK'd).
            $table->string('banque', 100)->nullable();
            $table->date('date_reception');
            // Garantie (À encaisser) | À déposer
            $table->string('type', 30);
            $table->date('date_echeance')->nullable();
            // En possession -> Déposé (Remise à la banque) -> Encaissé | Rejeté
            $table->string('statut', 20)->default('En possession');
            $table->text('note')->nullable();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('agent_id')->constrained('employees')->restrictOnDelete();
            $table->timestamps();

            $table->index('student_id');
            $table->index(['statut', 'date_echeance']);
        });

        // Payments made using a tracked chèque link back to it — the sum of
        // these rows' montant is the chèque's used amount ("Reste" = montant
        // minus this sum), mirroring the avance applications() pattern.
        Schema::table('encaissements', function (Blueprint $table): void {
            $table->foreignId('cheque_id')->nullable()->after('applied_from_encaissement_id')
                ->constrained('cheques')->restrictOnDelete();
            $table->index('cheque_id');
        });
    }

    public function down(): void
    {
        Schema::table('encaissements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cheque_id');
        });
        Schema::dropIfExists('cheques');
    }
};
