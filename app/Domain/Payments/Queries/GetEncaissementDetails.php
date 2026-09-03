<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Domain\Payments\Support\ResoudreAllocationsAvance;
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
        $encaissement->loadMissing([
            'student', 'fee.inscription.group', 'caisse', 'agent',
            // An avance carries no fee of its own — what it PAID lives on its
            // application rows, and the source avance is what an application
            // row came from. Both directions are loaded so the page can
            // explain either kind of row instead of showing « Aucun frais lié ».
            'applications.fee.inscription.group',
            'appliedFrom',
        ]);

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
            // Where this avance’s money FINALLY went (empty for a normal
            // payment): the whole chain, so an application that was
            // reconverted and re-applied names the fee its child row paid
            // rather than reading as an unlinked line (02/09/2026).
            'applications' => $encaissement->inscription_fee_id !== null ? [] : array_map(
                function (array $allocation): array {
                    /** @var Encaissement $row */
                    $row = $allocation['row'];

                    return [
                        // The row's own id — the Show page posts it to
                        // encaissements.detach to unlink that single line.
                        'id' => $row->id,
                        'reference' => $row->reference,
                        // Only a row still attached to a fee can be detached;
                        // a refunded one never (its money already left).
                        'detachable' => $row->inscription_fee_id !== null
                            && ! $row->remboursements()->exists(),
                        'frais' => ResoudreAllocationsAvance::libelle($allocation),
                        'groupe' => $allocation['kind'] === ResoudreAllocationsAvance::KIND_FRAIS
                            ? $row->fee?->inscription?->group?->nom
                            : null,
                        'montant' => number_format($allocation['montant'], 2, '.', ''),
                        'date' => $row->date_paiement?->format('d/m/Y'),
                        'showUrl' => route('backoffice.encaissements.show', $row),
                    ];
                },
                ResoudreAllocationsAvance::terminales([$encaissement->id])[$encaissement->id] ?? [],
            ),
            // Set when THIS row is itself an application of an earlier avance.
            'appliedFrom' => $encaissement->appliedFrom === null ? null : [
                'reference' => $encaissement->appliedFrom->reference,
                'montant' => number_format((float) $encaissement->appliedFrom->montant, 2, '.', ''),
                'date' => $encaissement->appliedFrom->date_paiement?->format('d/m/Y'),
                'showUrl' => route('backoffice.encaissements.show', $encaissement->appliedFrom),
            ],
            'isAvance' => $encaissement->inscription_fee_id === null,
            'montantUtilise' => number_format($encaissement->montantUtilise(), 2, '.', ''),
            'montantRestant' => number_format($encaissement->montantRestant(), 2, '.', ''),
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
