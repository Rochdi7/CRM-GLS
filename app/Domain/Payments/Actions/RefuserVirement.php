<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Employee;
use App\Models\Virement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * REFUS d'un virement par le comptable (07/10/2026) — le virement n'est pas
 * arrivé sur le compte, ou la preuve ne correspond pas.
 *
 * Aucun argent ne bouge : une demande en attente n'a jamais rien encaissé.
 * La ligne est CONSERVÉE avec son motif (jamais supprimée) — c'est la trace
 * qu'un étudiant a présenté une preuve que l'école n'a pas reconnue.
 */
final class RefuserVirement
{
    public function handle(Virement $virement, Employee $par, string $motif): Virement
    {
        return DB::transaction(function () use ($virement, $par, $motif): Virement {
            /** @var Virement $locked */
            $locked = Virement::query()->whereKey($virement->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isDecided()) {
                throw ValidationException::withMessages([
                    'statut' => __('This bank transfer request has already been processed.'),
                ]);
            }

            $locked->update([
                'statut' => Virement::STATUT_REFUSE,
                'motif_refus' => trim($motif),
                'decide_par_id' => $par->id,
                'decide_le' => now(),
            ]);

            activity('virement')
                ->performedOn($locked)
                ->event('virement_refuse')
                ->withProperties([
                    'reference' => $locked->reference,
                    'montant' => number_format((float) $locked->montant, 2, '.', ''),
                    'motif' => trim($motif),
                    'refuse_par' => $par->nomComplet(),
                ])
                ->log("Virement {$locked->reference} refusé");

            return $locked;
        });
    }
}
