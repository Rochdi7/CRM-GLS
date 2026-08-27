<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Remboursement;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the Remboursements list — extracted verbatim from
 * RemboursementsIndex::render() (center-scoping through the caisse relation,
 * same caisse/date-range/search filters). No detail/show page anywhere in
 * the live app (docs/phase-10-finance-mapping.md Q2: preserved, not added).
 */
final class GetRemboursementsList
{
    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(
        User $user,
        string $search = '',
        string $caisseFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        $remboursements = Remboursement::query()
            ->with(['beneficiaire', 'caisse', 'agent'])
            ->whereHas('caisse', function (Builder $q) use ($user): void {
                $this->centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
            })
            ->when($caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $caisseFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_remboursement', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('date_remboursement', '<=', $dateTo))
            // Year switcher: a remboursement belongs to the year its date
            // falls in; the active year is only the DEFAULT window — an
            // explicit date filter takes over.
            ->when(
                $dateFrom === '' && $dateTo === '' && $this->context->anneeDateRange() !== null,
                fn ($q) => $q->whereBetween('date_remboursement', $this->context->anneeDateRange()),
            )
            ->when($search !== '', function ($q) use ($search): void {
                $term = "%{$search}%";
                $q->where(function ($sub) use ($term): void {
                    $sub->where('reference', 'ilike', $term)
                        ->orWhereHas('beneficiaire', fn ($s) => $s
                            ->where('nom', 'ilike', $term)
                            ->orWhere('prenom', 'ilike', $term));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $remboursements->through(fn (Remboursement $r): array => [
            'id' => $r->id,
            'reference' => $r->reference,
            'beneficiaire' => $r->beneficiaire?->nomComplet(),
            'beneficiaireId' => $r->beneficiaire_id,
            'caisse' => $r->caisse?->nom,
            'caisseId' => $r->caisse_id,
            'montant' => number_format((float) $r->montant, 2, '.', ''),
            'dateRemboursement' => $r->date_remboursement?->toDateString(),
            'motif' => $r->motif,
            'note' => $r->note,
            'agent' => $r->agent?->nomComplet(),
        ]);

        return $remboursements;
    }

    /**
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function studentOptions(User $user): Collection
    {
        return Student::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get()
            ->map(fn (Student $s): array => ['id' => $s->id, 'nom' => $s->nomComplet()]);
    }

    private function scopeToActiveCenter($query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
