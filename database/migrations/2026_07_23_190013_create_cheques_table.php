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
            // Étudiant | Parents — who physically wrote the chèque.
            $table->string('source', 20);
            // Set when source = Étudiant (searchable picker); NULL otherwise.
            $table->foreignId('student_id')->nullable()->constrained('students')->restrictOnDelete();
            // Owner name when source = Parents, picked from a student's
            // parent on file (Student::parent_nom) — free text at the DB
            // layer, but the UI only offers known parents (§ ChequesIndex).
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
            // Physical return of a rejected chèque to its owner — off-ledger,
            // like the rest of this table. retourne_par_id is the employee
            // who physically handed the chèque back; agent_id (below) remains
            // "who originally received it".
            $table->timestamp('retourne_le')->nullable();
            $table->foreignId('retourne_par_id')->nullable()
                ->constrained('employees')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->foreignId('etablissement_id')->nullable()->constrained('etablissements')->nullOnDelete();
            $table->foreignId('agent_id')->constrained('employees')->restrictOnDelete();
            $table->timestamps();

            $table->index('student_id');
            $table->index(['statut', 'date_echeance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
