<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Support\CaisseLedger;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * VALIDATE step of the two-step till transfer (structure doc §7) — the core
 * fraud-prevention mechanism:
 *  - only the RECIPIENT (the employee who owns the destination till) may
 *    accept or refuse: the person whose caisse is about to be credited is the
 *    one who confirms they really received the money. A third party holding
 *    `cash-transfers.validate` must NOT be able to move money between two
 *    other people's tills — that is the hole this rule closes.
 *  - which implies the requester can never validate their own transfer (they
 *    are on the source side, not the destination),
 *  - balances move HERE, in one transaction, never at request time,
 *  - *_apres snapshots freeze both balances immediately after the move.
 */
final class ValiderTransfertCaisse
{
    public function __construct(private readonly CaisseLedger $ledger) {}

    public function handle(CaisseTransfer $transfer, Employee $validatedBy): CaisseTransfer
    {
        if ($transfer->requested_by === $validatedBy->id) {
            throw ValidationException::withMessages([
                'validated_by' => __('Le demandeur ne peut pas valider son propre transfert.'),
            ]);
        }

        // Recipient-only: the validator must own the DESTINATION till.
        // Checked against the employee's own caisses rather than
        // caisses.responsable_employee_id alone so an employee holding
        // several tills is still recognised as the recipient of any of them.
        $ownsDestination = $validatedBy->caisses()
            ->whereKey($transfer->caisse_destination_id)
            ->exists();

        if (! $ownsDestination) {
            throw ValidationException::withMessages([
                'validated_by' => __('Seul le destinataire du transfert peut le valider.'),
            ]);
        }

        return DB::transaction(function () use ($transfer, $validatedBy): CaisseTransfer {
            // The transfer row itself is re-read under lock and its status
            // re-checked HERE, not before the transaction: a double-click on
            // « Valider » (or a validate racing a cancel) would otherwise pass
            // the in-memory check twice and move the money twice.
            $transfer = CaisseTransfer::query()->whereKey($transfer->getKey())->lockForUpdate()->firstOrFail();

            if ($transfer->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
                throw ValidationException::withMessages([
                    'statut' => __('Ce transfert a déjà été traité.'),
                ]);
            }

            $source = Caisse::query()->lockForUpdate()->findOrFail($transfer->caisse_source_id);
            $destination = Caisse::query()->lockForUpdate()->findOrFail($transfer->caisse_destination_id);

            // Belt and braces with DemanderTransfertCaisse: cash only.
            if (! $source->isEspeces() || ! $destination->isEspeces()) {
                throw ValidationException::withMessages([
                    'caisse_destination_id' => __('A till transfer can only move cash between cash accounts.'),
                ]);
            }

            // Both legs go through the ledger so the journal shows the money
            // leaving one till AND arriving in the other — a transfer that only
            // logged one side would look like a loss.
            $this->ledger->debit(
                $source->id,
                (float) $transfer->montant,
                "Transfert {$transfer->reference} vers {$destination->nom}",
                $transfer,
                ['caisse_destination' => $destination->nom, 'valide_par' => $validatedBy->nomComplet()],
            );

            $this->ledger->credit(
                $destination->id,
                (float) $transfer->montant,
                "Transfert {$transfer->reference} depuis {$source->nom}",
                $transfer,
                ['caisse_source' => $source->nom, 'valide_par' => $validatedBy->nomComplet()],
            );

            $source->refresh();
            $destination->refresh();

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
