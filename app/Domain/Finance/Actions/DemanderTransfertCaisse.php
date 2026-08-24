<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;
use Illuminate\Validation\ValidationException;

/**
 * REQUEST step of the two-step till transfer (structure doc §7).
 * Captures the *_avant balance snapshots; caisses.solde is NOT touched —
 * a requested-but-unapproved transfer must not move real money.
 */
final class DemanderTransfertCaisse
{
    /**
     * @param array<string, mixed> $data validated StoreCaisseTransferRequest data
     */
    public function handle(array $data, Employee $requestedBy): CaisseTransfer
    {
        // A transfer is PHYSICAL cash changing hands: both ends must be cash
        // accounts (Caissière / Externe). The centre's TPE/Chèque/Virement
        // accounts have no owner to validate a receipt and hold no banknotes
        // — money reaches them only through a payment's method.
        foreach (['caisse_source_id', 'caisse_destination_id'] as $field) {
            $caisse = Caisse::query()->findOrFail((int) $data[$field]);

            if (! $caisse->isEspeces()) {
                throw ValidationException::withMessages([
                    $field => __('A till transfer can only move cash between cash accounts.'),
                ]);
            }
        }

        return CaisseTransfer::create([
            ...$data,
            'reference' => ReferenceGenerator::make('TRF', 'caisse_transfers'),
            'date_transfert' => now(),
            'solde_source_avant' => Caisse::query()->whereKey($data['caisse_source_id'])->value('solde'),
            'solde_dest_avant' => Caisse::query()->whereKey($data['caisse_destination_id'])->value('solde'),
            'statut' => CaisseTransfer::STATUT_EN_ATTENTE,
            'requested_by' => $requestedBy->id,
        ]);
    }
}
