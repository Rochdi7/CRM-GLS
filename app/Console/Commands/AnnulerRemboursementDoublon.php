<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Finance\Support\CaisseLedger;
use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Remboursement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reverses a refund that was recorded twice, by writing a COMPENSATING entry
 * — never by deleting the row (money records are append-only, CLAUDE.md §11).
 *
 * Why this exists (03/09/2026): the Remboursements list derived a refund's
 * centre from the till it was paid out of, so a student of one centre refunded
 * from a till homed to another was invisible on both. The cashier, seeing
 * nothing recorded, entered the same refund again — RMB-001 and RMB-002,
 * 300 DH each, one 300 DH refund actually handed over. The listing bug itself
 * is fixed (remboursements.etablissement_id); this repairs the money it left
 * behind.
 *
 * What it writes: a NEGATIVE-direction correction, i.e. the till is CREDITED
 * back by the duplicate's amount, journaled through CaisseLedger like every
 * other movement, and the duplicate is annotated so the trail explains itself.
 * The duplicate row stays exactly where it is.
 *
 * Dry-run by default; --apply commits.
 */
final class AnnulerRemboursementDoublon extends Command
{
    protected $signature = 'remboursements:annuler-doublon
        {reference : Référence du remboursement EN TROP (ex. RMB-002)}
        {--apply : Applique réellement la correction (sinon simulation)}';

    protected $description = "Annule un remboursement saisi en double par une écriture compensatoire (la caisse est recréditée, aucune ligne n'est supprimée)";

    public function handle(CaisseLedger $ledger): int
    {
        $reference = (string) $this->argument('reference');
        $apply = (bool) $this->option('apply');

        $doublon = Remboursement::query()
            ->with(['beneficiaire', 'caisse', 'agent'])
            ->where('reference', $reference)
            ->first();

        if ($doublon === null) {
            $this->error("Aucun remboursement avec la référence {$reference}.");

            return self::FAILURE;
        }

        if ($doublon->caisse === null) {
            $this->error("Le remboursement {$reference} n'a pas de caisse — rien à recréditer.");

            return self::FAILURE;
        }

        // Already reversed? The note carries the marker written below.
        if (str_contains((string) $doublon->note, self::MARKER)) {
            $this->warn("Le remboursement {$reference} a déjà été annulé — aucune action.");

            return self::SUCCESS;
        }

        $montant = (float) $doublon->montant;
        $caisse = $doublon->caisse;

        $this->line('');
        $this->line("  Remboursement en trop : <info>{$doublon->reference}</info>");
        $this->line('  Bénéficiaire          : '.($doublon->beneficiaire?->nomComplet() ?? '—'));
        $this->line('  Montant               : '.number_format($montant, 2, ',', ' ').' DH');
        $this->line("  Caisse à recréditer   : {$caisse->nom}");
        $this->line('  Solde actuel          : '.number_format((float) $caisse->solde, 2, ',', ' ').' DH');
        $this->line('  Solde après           : '.number_format((float) $caisse->solde + $montant, 2, ',', ' ').' DH');
        $this->line('');

        // Sanity: the other refunds of the same student, same amount, same day
        // — so the operator sees WHAT is being treated as the duplicate before
        // committing, rather than trusting the reference blindly.
        $jumeaux = Remboursement::query()
            ->where('beneficiaire_id', $doublon->beneficiaire_id)
            ->whereDate('date_remboursement', $doublon->date_remboursement)
            ->where('montant', $doublon->montant)
            ->whereKeyNot($doublon->id)
            ->get();

        if ($jumeaux->isEmpty()) {
            $this->warn('  ⚠ Aucun autre remboursement identique (même étudiant, même montant, même date).');
            $this->warn("    Vérifiez que {$reference} est bien le doublon avant d'appliquer.");
        } else {
            $this->line('  Conservé(s) :');
            foreach ($jumeaux as $j) {
                $this->line("    - {$j->reference} ({$j->caisse?->nom})");
            }
        }

        $this->line('');

        if (! $apply) {
            $this->warn('  SIMULATION — relancez avec --apply pour appliquer.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($doublon, $caisse, $montant, $ledger): void {
            // Credit the till back. Journaled like any other movement, with
            // the same centre stamp the refund itself carries, so the caisse
            // journal explains the correction instead of showing a jump.
            $ledger->credit(
                (int) $caisse->id,
                $montant,
                "Annulation du doublon {$doublon->reference}",
                $doublon,
                [
                    'beneficiaire_id' => $doublon->beneficiaire_id,
                    'etablissement_id' => $doublon->etablissement_id ?? $caisse->etablissement_id,
                    'correction' => 'doublon',
                ],
            );

            $note = trim((string) $doublon->note);
            $suffix = self::MARKER.' le '.now()->format('d/m/Y').' — saisi en double, caisse recréditée de '
                .number_format($montant, 2, ',', ' ').' DH.';

            $doublon->update([
                'note' => $note === '' ? $suffix : $note."\n".$suffix,
            ]);
        });

        $this->info('  ✔ Correction appliquée. La caisse a été recréditée et le remboursement annoté.');
        $this->line('  Nouveau solde : '.number_format((float) $caisse->fresh()->solde, 2, ',', ' ').' DH');

        return self::SUCCESS;
    }

    /** Written into the duplicate's note — also what makes the command idempotent. */
    private const MARKER = '[ANNULÉ]';
}
