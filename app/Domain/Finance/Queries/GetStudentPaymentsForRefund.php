<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Encaissement;
use Illuminate\Support\Collection;

/**
 * A student's refundable payments, for the Remboursement form's "which
 * payment are we refunding?" picker (docs pic: selecting a bénéficiaire
 * lists their payments).
 *
 * ⚠ Avances ARE listed (31/08/2026). They used to be filtered out with
 * `whereNotNull('inscription_fee_id')` on the theory that "there's nothing
 * paid-for to refund against yet" — but that is exactly backwards: an
 * avance is money the school HOLDS and has not earned, so it is the most
 * refundable thing there is, and EnregistrerRemboursement already has a
 * dedicated branch for it (capped at Encaissement::montantRestant(), with
 * the refund counting as "used" so the same dirhams can't also be applied
 * to a fee). Excluding them here made that branch unreachable from the UI:
 * a cashier who converted a payment into an avance — the normal « changement
 * de groupe » / dossier-clos flow — watched the row disappear from the
 * picker and could no longer refund it.
 *
 * `montantRemboursable` is therefore the ROW's own rule, not a subtraction
 * the client re-derives: an avance can give back only what is still
 * unallocated, an ordinary fee payment only what it brought in minus what
 * was already refunded. Application rows (applied_from_encaissement_id set,
 * fee detached) are excluded — that money is the parent avance's, and
 * refunding it here would let the same dirhams leave the till twice.
 */
final class GetStudentPaymentsForRefund
{
    /**
     * @return Collection<int, array{
     *     id: int, reference: string, montant: string, methode: string,
     *     date: ?string, feeNom: ?string, isAvance: bool,
     *     dejaRembourse: string, montantRemboursable: string,
     * }>
     */
    public function __invoke(int $studentId): Collection
    {
        return Encaissement::query()
            ->with('fee')
            ->withSum('remboursements as rembourse_sum', 'montant')
            ->withSum('applications as applique_sum', 'montant')
            ->where('student_id', $studentId)
            // An "apply" row is not money received — it is a slice of its
            // parent avance already spent on a fee. It is refunded by
            // refunding the parent, never on its own.
            ->whereNull('applied_from_encaissement_id')
            ->latest('date_paiement')
            ->get()
            ->map(function (Encaissement $e): array {
                $dejaRembourse = round((float) ($e->rembourse_sum ?? 0), 2);
                $applique = round((float) ($e->applique_sum ?? 0), 2);

                // An avance gives back only what is still unallocated
                // (applied + already refunded is gone); a fee payment gives
                // back only what it brought in, less prior refunds. Same
                // rule EnregistrerRemboursement enforces inside its locked
                // transaction — this is the UI's preview of it, never the
                // authority.
                $remboursable = $e->isAvance()
                    ? (float) $e->montant - $applique - $dejaRembourse
                    : (float) $e->montant - $dejaRembourse;

                return [
                    'id' => $e->id,
                    'reference' => $e->reference,
                    'montant' => number_format((float) $e->montant, 2, '.', ''),
                    'methode' => $e->methode,
                    'date' => $e->date_paiement?->format('d/m/Y'),
                    'feeNom' => $e->fee?->nom,
                    'isAvance' => $e->isAvance(),
                    'dejaRembourse' => number_format($dejaRembourse, 2, '.', ''),
                    'montantRemboursable' => number_format(round(max(0.0, $remboursable), 2), 2, '.', ''),
                ];
            })
            // Nothing left to give back = nothing to offer. A fully applied
            // avance or a fully refunded payment would otherwise sit in the
            // picker only to be rejected by the action.
            ->filter(fn (array $row): bool => (float) $row['montantRemboursable'] > 0)
            ->values();
    }
}
