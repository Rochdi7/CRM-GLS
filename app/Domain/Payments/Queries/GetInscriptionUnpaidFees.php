<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Collection;

/**
 * Powers the create form's cascading fee-lines lookup — extracted verbatim
 * from EncaissementsIndex::loadPaymentLines()/statutForFee(): one row per
 * unpaid fee of the selected inscription (fully-paid fees are excluded
 * entirely — matches `resteDuFeeById() > 0`), ordered by due date. Live
 * dû/payé/reste/statut are always recomputed here, never read from a
 * cached column, exactly like the Livewire cascade.
 */
final class GetInscriptionUnpaidFees
{
    /**
     * @return Collection<int, array{id:int, nom:string, montantInitial:string, paye:string, reste:string, statut:string, dateEcheance:?string}>
     */
    public function __invoke(Inscription $inscription): Collection
    {
        return $inscription->fees()
            ->orderBy('date_echeance')
            ->get()
            ->map(function (InscriptionFee $fee): ?array {
                $paye = (float) $fee->montantPaye();
                $reste = round(max(0, (float) $fee->montant - $paye), 2);

                if ($reste <= 0) {
                    return null;
                }

                return [
                    'id' => $fee->id,
                    'nom' => $fee->nom,
                    'montantInitial' => number_format((float) $fee->montant, 2, '.', ''),
                    'paye' => number_format($paye, 2, '.', ''),
                    'reste' => number_format($reste, 2, '.', ''),
                    'statut' => $this->statutForFee((float) $fee->montant, $paye),
                    'dateEcheance' => $fee->date_echeance?->toDateString(),
                ];
            })
            ->filter()
            ->values();
    }

    private function statutForFee(float $montant, float $paye): string
    {
        if ($paye <= 0) {
            return InscriptionFee::STATUT_NON_PAYE;
        }

        if ($paye >= $montant) {
            return InscriptionFee::STATUT_PAYE;
        }

        return InscriptionFee::STATUT_PAYE_PARTIELLEMENT;
    }
}
