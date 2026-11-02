<?php

declare(strict_types=1);

namespace App\Domain\Finance\Actions;

use App\Models\DemandeRemboursement;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * REFUS d'une demande de remboursement (02/11/2026). Aucun argent ne bouge —
 * une demande n'a jamais rien débité. La ligne est CONSERVÉE avec son motif,
 * jamais supprimée.
 */
final class RefuserDemandeRemboursement
{
    public function handle(DemandeRemboursement $demande, Employee $par, string $motif): DemandeRemboursement
    {
        return DB::transaction(function () use ($demande, $par, $motif): DemandeRemboursement {
            /** @var DemandeRemboursement $locked */
            $locked = DemandeRemboursement::query()->whereKey($demande->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isEnAttente()) {
                throw ValidationException::withMessages([
                    'demande' => __('This refund request has already been processed.'),
                ]);
            }

            $locked->update([
                'statut' => DemandeRemboursement::STATUT_REFUSEE,
                'motif_refus' => trim($motif),
                'decide_par_id' => $par->id,
                'decide_le' => now(),
            ]);

            activity('demande_remboursement')
                ->performedOn($locked)
                ->event('demande_remboursement_refusee')
                ->withProperties([
                    'reference' => $locked->reference,
                    'montant' => number_format((float) $locked->montant, 2, '.', ''),
                    'motif' => trim($motif),
                    'refuse_par' => $par->nomComplet(),
                ])
                ->log("Demande de remboursement {$locked->reference} refusée");

            return $locked;
        });
    }
}
