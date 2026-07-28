<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Employee;

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
