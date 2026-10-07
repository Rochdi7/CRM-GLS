<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Virement;
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
     * @return Collection<int, array{id:int, nom:string, montantInitial:string, paye:string, reste:string, virementEnAttente:string, statut:string, dateEcheance:?string}>
     */
    public function __invoke(Inscription $inscription): Collection
    {
        // Virements déclarés et pas encore vérifiés par le comptable, par
        // frais — EN LOT (une requête pour toute l'inscription, §17). Ils ne
        // réduisent pas le reste dû (rien n'est encaissé tant que le
        // comptable n'a pas validé) : la colonne le DIT pour que la caissière
        // ne déclare pas deux fois le même virement, et DemanderVirement
        // plafonne une nouvelle demande au reste MOINS ces montants.
        $virementsEnAttente = Virement::query()
            ->where('inscription_id', $inscription->id)
            ->enAttente()
            ->selectRaw('inscription_fee_id, sum(montant) as total')
            ->groupBy('inscription_fee_id')
            ->pluck('total', 'inscription_fee_id');

        return $inscription->fees()
            // A hidden line (RetirerFraisGroupe / hideFee) is not owed and
            // must never be offered for payment (audit R-01).
            ->whereNull('masque_le')
            ->orderBy('date_echeance')
            ->get()
            ->map(function (InscriptionFee $fee) use ($virementsEnAttente): ?array {
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
                    'virementEnAttente' => number_format((float) ($virementsEnAttente[$fee->id] ?? 0), 2, '.', ''),
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
