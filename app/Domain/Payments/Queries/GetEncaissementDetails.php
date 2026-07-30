<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Encaissement;

/**
 * Extracted from resources/views/backoffice/encaissements/show.blade.php's
 * own @php block + EncaissementController::show()'s eager loads. Fee
 * due/paid/remaining totals computed here exactly as the Blade view did —
 * never recalculated client-side. No delete/correction workflow — a
 * payment is never deleted.
 */
final class GetEncaissementDetails
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Encaissement $encaissement): array
    {
        $encaissement->loadMissing(['student', 'fee.inscription.group', 'caisse', 'agent']);

        $fee = $encaissement->fee;
        $inscription = $fee?->inscription;
        $du = (float) ($fee?->montant ?? 0);
        $paye = $fee ? (float) $fee->encaissements()->sum('montant') : 0.0;
        $reste = max(0, $du - $paye);

        return [
            'id' => $encaissement->id,
            'reference' => $encaissement->reference,
            'montant' => number_format((float) $encaissement->montant, 2, '.', ''),
            'methode' => $encaissement->methode,
            'date' => $encaissement->date_paiement?->format('d/m/Y'),
            'student' => $encaissement->student?->nomComplet(),
            'studentShowUrl' => $encaissement->student ? route('backoffice.students.show', $encaissement->student) : null,
            'inscriptionReference' => $inscription?->reference,
            'inscriptionShowUrl' => $inscription ? route('backoffice.inscriptions.show', $inscription) : null,
            'groupe' => $inscription?->group?->nom,
            'caisse' => $encaissement->caisse?->nom,
            'agent' => $encaissement->agent?->nomComplet(),
            'note' => $encaissement->note,
            'cheque' => $encaissement->methode === Encaissement::METHODE_CHEQUE ? [
                'numero' => $encaissement->numero_cheque,
                'banque' => $encaissement->banque,
                'dateEcheance' => $encaissement->date_echeance_cheque?->format('d/m/Y'),
            ] : null,
            'fee' => $fee === null ? null : [
                'nom' => $fee->nom,
                'dateEcheance' => $fee->date_echeance?->format('d/m/Y'),
                'totalDu' => number_format($du, 2, '.', ''),
                'totalPaye' => number_format($paye, 2, '.', ''),
                'reste' => number_format($reste, 2, '.', ''),
                'statut' => $fee->statut,
            ],
        ];
    }
}
