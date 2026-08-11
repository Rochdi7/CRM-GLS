<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Converts fee-attached payments of ONE inscription back into unallocated
 * avances — detaches inscription_fee_id (the row itself is never deleted,
 * money records are append-only, CLAUDE.md §11) and recomputes each touched
 * fee's statut, which drops back to Non payé / Payé partiellement so the fee
 * shows as owed again. caisses.solde is untouched: the money stays in the
 * till, only its allocation changes.
 *
 * This is the "changement de groupe" money-move flow: the old inscription's
 * payments become avances (traceable, with montant utilisé/restant), then
 * AppliquerAvance spends them on the new inscription's fees.
 *
 * An "apply" row (applied_from_encaissement_id set) is converted the same
 * way — its fee is detached but the link to the parent avance is kept, so
 * the parent's used amount stays correct while the detached row's own
 * montant becomes re-allocatable (see Encaissement::isAvance()).
 */
final class ConvertirEncaissementsEnAvance
{
    /**
     * @param  list<int>  $encaissementIds
     * @return int number of payments converted
     */
    public function handle(Inscription $inscription, array $encaissementIds): int
    {
        return DB::transaction(function () use ($inscription, $encaissementIds): int {
            $encaissements = Encaissement::query()
                ->with('fee')
                ->whereIn('id', $encaissementIds)
                ->lockForUpdate()
                ->get();

            foreach ($encaissements as $encaissement) {
                if ($encaissement->fee === null || $encaissement->fee->inscription_id !== $inscription->id) {
                    throw ValidationException::withMessages([
                        'encaissement_ids' => __('One of the selected payments does not belong to this registration.'),
                    ]);
                }

                if ($encaissement->remboursements()->exists()) {
                    throw ValidationException::withMessages([
                        'encaissement_ids' => __('A refunded payment cannot be converted into an advance.'),
                    ]);
                }
            }

            $feeIds = $encaissements->pluck('inscription_fee_id')->unique()->all();

            foreach ($encaissements as $encaissement) {
                // Audit-logged (LogsActivity tracks inscription_fee_id).
                $encaissement->update(['inscription_fee_id' => null]);
            }

            InscriptionFee::query()->whereIn('id', $feeIds)->get()
                ->each(fn (InscriptionFee $fee) => $this->recalculerStatutFee($fee));

            return $encaissements->count();
        });
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
