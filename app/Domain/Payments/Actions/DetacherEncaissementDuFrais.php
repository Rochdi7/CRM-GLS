<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Encaissement;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Detaches ONE payment from the fee it settles — the single-row counterpart
 * of ConvertirEncaissementsEnAvance, driven from a payment's own Show page
 * instead of an inscription-wide selection.
 *
 * The row is never deleted and `caisses.solde` never moves (money records
 * are append-only, CLAUDE.md §11): only `inscription_fee_id` is cleared, so
 * the fee drops back to Non payé / Payé partiellement and the money becomes
 * re-applicable. An APPLICATION row keeps `applied_from_encaissement_id`, so
 * its parent avance's « montant utilisé » stays correct while the detached
 * amount returns to the re-allocatable pool (Encaissement::isAvance()).
 *
 * Reserved to super-admins (`payments.detach`, superAdminOnly): making a
 * settled fee owed again silently rewrites what a student is billed.
 */
final class DetacherEncaissementDuFrais
{
    public function handle(Encaissement $encaissement): Encaissement
    {
        return DB::transaction(function () use ($encaissement): Encaissement {
            // Re-read locked: the guards below decide on balances, and a
            // double-click must not detach a row twice (§11).
            $row = Encaissement::query()->whereKey($encaissement->getKey())->lockForUpdate()->firstOrFail();

            if ($row->inscription_fee_id === null) {
                throw ValidationException::withMessages([
                    'id' => __('This payment is not attached to any fee.'),
                ]);
            }

            if ($row->remboursements()->exists()) {
                throw ValidationException::withMessages([
                    'id' => __('A refunded payment cannot be converted into an advance.'),
                ]);
            }

            $fee = InscriptionFee::query()->whereKey($row->inscription_fee_id)->lockForUpdate()->first();

            $row->update(['inscription_fee_id' => null]);

            if ($fee !== null) {
                $paye = $fee->montantPaye();

                $fee->update([
                    'statut' => match (true) {
                        $paye >= (float) $fee->montant => InscriptionFee::STATUT_PAYE,
                        $paye > 0 => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
                        default => InscriptionFee::STATUT_NON_PAYE,
                    },
                ]);
            }

            return $row->refresh();
        });
    }
}
