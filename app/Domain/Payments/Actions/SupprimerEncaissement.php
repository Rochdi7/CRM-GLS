<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Caisse;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use App\Domain\Finance\Support\CaisseLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Exact inverse of EnregistrerEncaissement, in ONE transaction:
 *  1. decrements the till balance it had incremented (caisses.solde is
 *     application-maintained — deleting the row without this silently
 *     corrupts the till),
 *  2. deletes the encaissement row,
 *  3. recomputes the fee's payment statut.
 *
 * Deliberately narrow (CLAUDE.md §11 keeps money records append-only for
 * everyone else): this exists ONLY for the `payments.delete` permission,
 * which is granted by a super-admin. Corrections in normal operation should
 * still use a compensating entry (remboursement), not a delete.
 *
 * Refused when the row is entangled with other records, because unwinding
 * those cannot be done correctly here:
 *  - an avance that has already been applied to fees,
 *  - a payment made with a tracked chèque (Cheques module owns that lifecycle),
 *  - a payment that has already been (partly) refunded.
 */
final class SupprimerEncaissement
{
    public function __construct(private readonly CaisseLedger $ledger) {}

    public function handle(Encaissement $encaissement): void
    {
        DB::transaction(function () use ($encaissement): void {
            // Re-read under a row lock so a concurrent apply/convert can't slip
            // in between the guard checks and the delete.
            $locked = Encaissement::query()
                ->whereKey($encaissement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->guard($locked);

            $fee = $locked->fee;

            // An "apply" row only reallocates an existing avance — it never
            // incremented solde (see AppliquerAvance), so it must not decrement
            // it either. Only real money-in rows move the till.
            if ($locked->applied_from_encaissement_id === null) {
                $this->ledger->debit(
                    (int) $locked->caisse_id,
                    (float) $locked->montant,
                    "Annulation de l'encaissement {$locked->reference}",
                    $locked,
                    ['motif_detail' => 'Suppression du paiement'],
                );
            }

            $locked->delete();

            if ($fee !== null) {
                $this->recalculerStatutFee($fee->fresh());
            }
        });
    }

    private function guard(Encaissement $encaissement): void
    {
        if ($encaissement->isAvance() && $encaissement->montantUtilise() > 0) {
            throw ValidationException::withMessages([
                'encaissement' => __('This advance has already been applied to fees. Remove those allocations first.'),
            ]);
        }

        // A refunded payment already moved money OUT of the till once
        // (EnregistrerRemboursement). Deleting it would debit the same
        // amount again AND orphan the remboursement (FK nullOnDelete), so
        // the trail could no longer explain the outflow.
        if ($encaissement->remboursements()->exists()) {
            throw ValidationException::withMessages([
                'encaissement' => __('A refunded payment cannot be deleted.'),
            ]);
        }

        if ($encaissement->cheque_id !== null) {
            throw ValidationException::withMessages([
                'encaissement' => __('A payment linked to a tracked cheque cannot be deleted.'),
            ]);
        }
    }

    private function recalculerStatutFee(InscriptionFee $fee): void
    {
        $paye = $fee->montantPaye();

        $fee->update([
            'statut' => match (true) {
                $paye >= (float) $fee->montant => InscriptionFee::STATUT_PAYE,
                $paye > 0 => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
                default => InscriptionFee::STATUT_NON_PAYE,
            },
        ]);
    }
}
