<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * VALIDATE step of the two-step till transfer (structure doc §7) — the core
 * fraud-prevention mechanism:
 *  - only a DIFFERENT employee than the requester may validate,
 *  - balances move HERE, in one transaction, never at request time,
 *  - *_apres snapshots freeze both balances immediately after the move.
 */
final class ValiderTransfertCaisse
{
    public function handle(CaisseTransfer $transfer, Employee $validatedBy): CaisseTransfer
    {
        if ($transfer->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
            throw ValidationException::withMessages([
                'statut' => __('Ce transfert a déjà été traité.'),
            ]);
        }

        if ($transfer->requested_by === $validatedBy->id) {
            throw ValidationException::withMessages([
                'validated_by' => __('Le demandeur ne peut pas valider son propre transfert.'),
            ]);
        }

        return DB::transaction(function () use ($transfer, $validatedBy): CaisseTransfer {
            $source = Caisse::query()->lockForUpdate()->findOrFail($transfer->caisse_source_id);
            $destination = Caisse::query()->lockForUpdate()->findOrFail($transfer->caisse_destination_id);

            $source->decrement('solde', (float) $transfer->montant);
            $destination->increment('solde', (float) $transfer->montant);

            $transfer->update([
                'statut' => CaisseTransfer::STATUT_VALIDE,
                'validated_by' => $validatedBy->id,
                'solde_source_apres' => $source->fresh()->solde,
                'solde_dest_apres' => $destination->fresh()->solde,
            ]);

            return $transfer;
        });
    }
}
