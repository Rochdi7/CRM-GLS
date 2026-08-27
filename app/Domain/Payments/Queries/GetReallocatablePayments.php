<?php

declare(strict_types=1);

namespace App\Domain\Payments\Queries;

use App\Models\Encaissement;
use App\Services\Context\CurrentContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * The payments offered on the « Déplacer des encaissements » screen.
 *
 * Deliberately NOT year-scoped: the whole point of the screen is to move
 * money booked against the wrong année, so hiding the other year would hide
 * exactly the rows the operator came to fix. The CENTRE is still enforced
 * (active context), like everywhere else.
 *
 * Read-only — the move itself goes through ConvertirEncaissementsEnAvance
 * + AppliquerAvance, so the caisse invariants (CLAUDE.md §11) are untouched.
 */
final readonly class GetReallocatablePayments
{
    private const int DEFAULT_PER_PAGE = 50;

    public function __construct(private CurrentContext $context) {}

    /**
     * @return array{data: LengthAwarePaginator, montantTotal: string}
     */
    public function __invoke(
        string $search = '',
        ?int $groupId = null,
        string $fraisFilter = '',
        ?int $anneeId = null,
        int $perPage = self::DEFAULT_PER_PAGE,
    ): array {
        $base = Encaissement::query()
            ->whereNotNull('inscription_fee_id')
            ->when($this->context->etablissementId(), fn ($q, $centreId) => $q->where('etablissement_id', $centreId))
            ->when($groupId, fn ($q, $id) => $q->whereHas('fee.inscription', fn ($i) => $i->where('group_id', $id)))
            ->when($anneeId, fn ($q, $id) => $q->whereHas('fee.inscription', fn ($i) => $i->where('annee_scolaire_id', $id)))
            ->when($fraisFilter !== '', fn ($q) => $q->whereHas('fee', fn ($f) => $f->where('nom', $fraisFilter)))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('reference', 'ilike', "%{$search}%")
                        ->orWhere('legacy_ref', 'ilike', "%{$search}%")
                        ->orWhereHas('student', fn ($s) => $s
                            ->where('nom', 'ilike', "%{$search}%")
                            ->orWhere('prenom', 'ilike', "%{$search}%"));
                });
            });

        $montantTotal = (clone $base)->sum('montant');

        $rows = $base
            ->with(['student:id,nom,prenom', 'fee:id,inscription_id,nom', 'fee.inscription:id,group_id,annee_scolaire_id',
                'fee.inscription.group:id,nom', 'fee.inscription.anneeScolaire:id,nom'])
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Encaissement $e): array => [
                'id' => $e->id,
                'reference' => $e->reference,
                'legacyRef' => $e->legacy_ref,
                'etudiant' => trim(($e->student?->prenom ?? '').' '.($e->student?->nom ?? '')),
                'montant' => (string) $e->montant,
                'methode' => $e->methode,
                'datePaiement' => $e->date_paiement?->toDateString(),
                'frais' => $e->fee?->nom,
                'groupe' => $e->fee?->inscription?->group?->nom,
                'annee' => $e->fee?->inscription?->anneeScolaire?->nom,
                'inscriptionId' => $e->fee?->inscription_id,
            ]);

        return [
            'data' => $rows,
            'montantTotal' => number_format((float) $montantTotal, 2, '.', ''),
        ];
    }
}
