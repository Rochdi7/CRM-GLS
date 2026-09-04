<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;

/**
 * Extracted from resources/views/backoffice/caisses/show.blade.php's own
 * inline @php block (that view queried the last 10 rows of each movement
 * type directly — its own comment noted "the controller stays untouched").
 * Moved here verbatim: same 4 relations, same limit(10), same ordering.
 * Read-only — no create/update/delete anywhere in this class.
 */
final class GetCaisseDetails
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(Caisse $caisse): array
    {
        $caisse->loadMissing(['etablissement', 'responsable']);

        // Same movement filters as GetCaisseJournal: this page shows what
        // actually MOVED the till, so it must reconcile with the `solde`
        // printed above it.
        //  - an "apply" row (applied_from_encaissement_id) only re-allocates
        //    an avance already counted once — AppliquerAvance never credits;
        //  - a pending/refused dépense debited nothing (approval flow).
        // Listing either made this page contradict the balance beside it.
        // `etablissement` eager-loaded for the Centre column: ONE till per
        // employee, but a cashier working across centres books payments in
        // each of them, so the till's own centre is not the payment's
        // (CLAUDE.md §11, « Centre dimension on the ledger »).
        $encaissements = $caisse->encaissements()
            ->whereNull('applied_from_encaissement_id')
            ->with(['student', 'etablissement'])->latest('date_paiement')->limit(10)->get();
        $depenses = $caisse->depenses()
            ->where('statut', Depense::STATUT_APPROUVEE)
            ->with('typeDepense')->latest('date_depense')->limit(10)->get();
        $remboursements = $caisse->remboursements()->with('beneficiaire')->latest('date_remboursement')->limit(10)->get();
        $transfers = CaisseTransfer::query()
            ->with(['caisseSource', 'caisseDestination'])
            ->where('caisse_source_id', $caisse->id)
            ->orWhere('caisse_destination_id', $caisse->id)
            ->latest('date_transfert')
            ->limit(10)
            ->get();

        return [
            'id' => $caisse->id,
            'nom' => $caisse->nom,
            'centre' => $caisse->etablissement?->nom_centre,
            'responsable' => $caisse->responsable?->nomComplet(),
            'solde' => number_format((float) $caisse->solde, 2, '.', ''),
            'statut' => $caisse->statut,
            'encaissements' => $encaissements->map(fn ($enc): array => [
                'reference' => $enc->reference,
                'label' => $enc->student?->nomComplet() ?? '—',
                'date' => $enc->date_paiement?->format('d/m/Y'),
                'montant' => number_format((float) $enc->montant, 2, '.', ''),
                'extra' => $enc->methode,
                // The payment's OWN centre — where the money was taken in,
                // which may differ from the till's centre.
                'centre' => $enc->etablissement?->nom_centre,
            ])->values()->all(),
            'depenses' => $depenses->map(fn ($dep): array => [
                'reference' => $dep->reference,
                'label' => $dep->typeDepense?->nom ?? '—',
                'date' => $dep->date_depense?->format('d/m/Y'),
                'montant' => number_format((float) $dep->montant, 2, '.', ''),
            ])->values()->all(),
            'remboursements' => $remboursements->map(fn ($rmb): array => [
                'reference' => $rmb->reference,
                'label' => $rmb->beneficiaire?->nomComplet() ?? '—',
                'date' => $rmb->date_remboursement?->format('d/m/Y'),
                'montant' => number_format((float) $rmb->montant, 2, '.', ''),
            ])->values()->all(),
            'transfers' => $transfers->map(function (CaisseTransfer $transfer) use ($caisse): array {
                $sortant = $transfer->caisse_source_id === $caisse->id;

                return [
                    'reference' => $transfer->reference,
                    'label' => $sortant ? ($transfer->caisseDestination?->nom ?? '—') : ($transfer->caisseSource?->nom ?? '—'),
                    'date' => $transfer->date_transfert?->format('d/m/Y'),
                    'montant' => number_format((float) $transfer->montant, 2, '.', ''),
                    'direction' => $sortant ? 'out' : 'in',
                    'statut' => $transfer->statut,
                ];
            })->values()->all(),
        ];
    }
}
