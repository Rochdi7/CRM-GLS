<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Encaissement;
use App\Models\Inscription;
use Illuminate\Support\Collection;

/**
 * Every fee-attached payment of one inscription — powers the "Convertir en
 * avance" modal's checklist (pick an inscription, tick the payments to
 * detach). Rows already refunded are flagged (`rembourse`) so the UI can
 * disable them — ConvertirEncaissementsEnAvance refuses them server-side
 * anyway.
 */
final class GetInscriptionPayments
{
    /**
     * @return Collection<int, array{
     *     id: int, reference: string, feeNom: ?string, montant: string,
     *     methode: string, datePaiement: ?string, rembourse: bool,
     * }>
     */
    public function __invoke(Inscription $inscription): Collection
    {
        return Encaissement::query()
            ->with('fee')
            ->withExists('remboursements')
            ->whereHas('fee', fn ($q) => $q->where('inscription_id', $inscription->id))
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get()
            ->map(fn (Encaissement $e): array => [
                'id' => $e->id,
                'reference' => $e->reference,
                'feeNom' => $e->fee?->nom,
                'montant' => number_format((float) $e->montant, 2, '.', ''),
                'methode' => $e->methode,
                'datePaiement' => $e->date_paiement?->toDateString(),
                'rembourse' => (bool) $e->remboursements_exists,
            ]);
    }
}
