<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\CaisseTransfer;

/**
 * Extracted from resources/views/backoffice/caisse-transfers/show.blade.php
 * + CaisseTransferController::show()'s eager loads. No action here — the
 * two-step request/validate workflow's actual controls live on the
 * Livewire caisse-transfers index tab (structure doc §7), not this page.
 */
final class GetCaisseTransferDetails
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(CaisseTransfer $transfer): array
    {
        $transfer->loadMissing(['caisseSource', 'caisseDestination', 'requestedBy', 'validatedBy']);

        return [
            'id' => $transfer->id,
            'reference' => $transfer->reference,
            'caisseSource' => $transfer->caisseSource?->nom,
            'caisseDestination' => $transfer->caisseDestination?->nom,
            'montant' => number_format((float) $transfer->montant, 2, '.', ''),
            'date' => $transfer->date_transfert?->format('d/m/Y H:i'),
            'statut' => $transfer->statut,
            'isPending' => $transfer->statut === CaisseTransfer::STATUT_EN_ATTENTE,
            'requestedBy' => $transfer->requestedBy?->nomComplet(),
            'validatedBy' => $transfer->validatedBy?->nomComplet(),
            'note' => $transfer->note,
            'soldeSourceAvant' => $transfer->solde_source_avant === null ? null : number_format((float) $transfer->solde_source_avant, 2, '.', ''),
            'soldeSourceApres' => $transfer->solde_source_apres === null ? null : number_format((float) $transfer->solde_source_apres, 2, '.', ''),
            'soldeDestAvant' => $transfer->solde_dest_avant === null ? null : number_format((float) $transfer->solde_dest_avant, 2, '.', ''),
            'soldeDestApres' => $transfer->solde_dest_apres === null ? null : number_format((float) $transfer->solde_dest_apres, 2, '.', ''),
        ];
    }
}
