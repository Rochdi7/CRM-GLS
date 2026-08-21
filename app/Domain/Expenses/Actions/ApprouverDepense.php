<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Actions;

use App\Domain\Finance\Support\CaisseLedger;
use App\Models\Depense;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * APPROVE step of the dépense request flow — this is where the money
 * actually leaves the till.
 *
 * Held until here: EnregistrerDepense created the row "En attente" WITHOUT
 * debiting anything, so approving is the single moment caisses.solde moves
 * for that expense. Guarded against double-spend: a dépense that already has
 * a decision is refused outright, and the check + the debit share one
 * transaction with the row locked.
 */
final class ApprouverDepense
{
    public function __construct(private readonly CaisseLedger $ledger) {}

    public function handle(Depense $depense, Employee $approvedBy): Depense
    {
        return DB::transaction(function () use ($depense, $approvedBy): Depense {
            // Re-read under lock: two admins clicking "Approuver" at the same
            // instant must not debit the till twice.
            /** @var Depense $locked */
            $locked = Depense::query()->whereKey($depense->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isDecided()) {
                throw ValidationException::withMessages([
                    'statut' => __('Cette dépense a déjà été traitée.'),
                ]);
            }

            $this->ledger->debit(
                (int) $locked->caisse_id,
                (float) $locked->montant,
                "Dépense {$locked->reference}",
                $locked,
                [
                    'type_depense_id' => $locked->type_depense_id,
                    'methode' => $locked->methode_paiement,
                    'approuve_par' => $approvedBy->nomComplet(),
                ],
            );

            $locked->update([
                'statut' => Depense::STATUT_APPROUVEE,
                'approved_by' => $approvedBy->id,
                'approved_at' => now(),
                'motif_refus' => null,
            ]);

            return $locked;
        });
    }
}
