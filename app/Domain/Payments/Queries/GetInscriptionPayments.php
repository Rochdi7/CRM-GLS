<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Domain\Payments\Support\ChequeOrigine;
use App\Domain\Payments\Support\ValiditeAvance;
use App\Models\Cheque;
use App\Models\Encaissement;
use App\Models\Inscription;
use Illuminate\Support\Collection;

/**
 * Every fee-attached payment of one inscription — powers the "Convertir en
 * avance" modal's checklist (pick an inscription, tick the payments to
 * detach). Rows already refunded are flagged (`rembourse`) so the UI can
 * disable them — ConvertirEncaissementsEnAvance refuses them server-side
 * anyway.
 *
 * `splittable` carries the action's OWN rule for a PARTIAL conversion
 * (« scinder » — keep part of the money on the original fee, release the
 * rest as an avance): refused on a hidden fee and on a rejected cheque,
 * because the kept part is re-applied to that fee through AppliquerAvance,
 * which refuses both. The modal disables the amount input and names the
 * reason (`splitBlocker`) instead of offering a split that would fail —
 * a read-model never re-derives a business rule (CLAUDE.md §5). A
 * whole-row conversion of such a payment stays allowed.
 *
 * `convertible` / `convertBlocker` carry the two refusals of the conversion
 * ITSELF: a refunded payment, and — for anyone but the super-admin — a
 * payment older than 14 days (ValiditeAvance). The row stays LISTED and
 * disabled with its reason, never removed: a cashier must see that the
 * payment exists and why she cannot release it.
 */
final class GetInscriptionPayments
{
    /**
     * @return Collection<int, array{
     *     id: int, reference: string, feeNom: ?string, montant: string,
     *     methode: string, datePaiement: ?string, rembourse: bool,
     *     splittable: bool, splitBlocker: ?string,
     *     convertible: bool, convertBlocker: ?string, ancien: bool,
     * }>
     */
    public function __invoke(Inscription $inscription, bool $peutOutrepasserDelai = false): Collection
    {
        $rows = Encaissement::query()
            ->with('fee')
            ->withExists('remboursements')
            ->whereHas('fee', fn ($q) => $q->where('inscription_id', $inscription->id))
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get();

        // The cheque behind each row, through the applied_from chain — an
        // application row of a cheque-funded avance has no cheque_id itself.
        $cheques = ChequeOrigine::pour($rows->pluck('id')->all());

        return $rows
            ->map(function (Encaissement $e) use ($cheques, $peutOutrepasserDelai): array {
                $ancien = ValiditeAvance::tropAncienPourConversion($e);
                $convertBlocker = match (true) {
                    (bool) $e->remboursements_exists => __('A refunded payment cannot be converted into an advance.'),
                    $ancien && ! $peutOutrepasserDelai => ValiditeAvance::motifConversionRefusee($e),
                    default => null,
                };

                $blocker = match (true) {
                    $e->fee?->estMasque() === true => __('Hidden fee: the payment can only be converted in full.'),
                    ($cheques[$e->id] ?? null)?->statut === Cheque::STATUT_REJETE => __('Rejected cheque: the payment cannot be split.'),
                    default => null,
                };

                return [
                    'id' => $e->id,
                    'reference' => $e->reference,
                    'feeNom' => $e->fee?->nom,
                    'montant' => number_format((float) $e->montant, 2, '.', ''),
                    'methode' => $e->methode,
                    'datePaiement' => $e->date_paiement?->toDateString(),
                    'rembourse' => (bool) $e->remboursements_exists,
                    'splittable' => $blocker === null,
                    'splitBlocker' => $blocker,
                    // Plus de 14 jours : affiché à tous, bloquant pour le
                    // guichet seulement.
                    'ancien' => $ancien,
                    'convertible' => $convertBlocker === null,
                    'convertBlocker' => $convertBlocker,
                ];
            });
    }
}
