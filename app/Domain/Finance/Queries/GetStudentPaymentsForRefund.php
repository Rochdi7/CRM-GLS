<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Encaissement;
use Illuminate\Support\Collection;

/**
 * A student's fee-targeted payments, for the Remboursement form's "which
 * payment are we refunding?" picker (docs pic: selecting a bénéficiaire
 * lists their payments). Unallocated avances (no fee attached — see
 * Encaissement::isAvance()) are excluded: there's nothing paid-for to
 * refund against yet.
 */
final class GetStudentPaymentsForRefund
{
    /**
     * @return Collection<int, array{
     *     id: int, reference: string, montant: string, methode: string,
     *     date: ?string, feeNom: ?string, dejaRembourse: string,
     * }>
     */
    public function __invoke(int $studentId): Collection
    {
        return Encaissement::query()
            ->with('fee')
            ->where('student_id', $studentId)
            ->whereNotNull('inscription_fee_id')
            ->latest('date_paiement')
            ->get()
            ->map(fn (Encaissement $e): array => [
                'id' => $e->id,
                'reference' => $e->reference,
                'montant' => number_format((float) $e->montant, 2, '.', ''),
                'methode' => $e->methode,
                'date' => $e->date_paiement?->format('d/m/Y'),
                'feeNom' => $e->fee?->nom,
                'dejaRembourse' => number_format((float) $e->remboursements()->sum('montant'), 2, '.', ''),
            ]);
    }
}
