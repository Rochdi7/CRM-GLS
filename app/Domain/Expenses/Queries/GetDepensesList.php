<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Queries;

use App\Models\Depense;
use App\Models\Group;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the Depenses list — extracted verbatim from
 * DepensesIndex::render() (center-scoping through the caisse relation, same
 * type/caisse/date-range/search filters, `montantTotal` computed over the
 * FULL filtered set, not just the current page).
 *
 * The "Paiement prof" system type is split out of the main list into its own
 * tab (see $scope) — those rows stay ordinary dépenses in every other
 * respect (same table, same caisse, same money invariants), they are only
 * listed apart so the Dépenses table stays readable.
 */
final class GetDepensesList
{
    public const DEFAULT_PER_PAGE = 15;

    /** Everything EXCEPT the "Paiement prof" system type — the Dépenses tab. */
    public const SCOPE_HORS_PAIEMENT_PROF = 'hors-paiement-prof';

    /** ONLY the "Paiement prof" system type — the Paiements prof tab. */
    public const SCOPE_PAIEMENT_PROF = 'paiement-prof';

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return array{data: LengthAwarePaginator, montantTotal: string}
     */
    public function __invoke(
        User $user,
        string $search = '',
        string $typeFilter = '',
        string $caisseFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        string $scope = self::SCOPE_HORS_PAIEMENT_PROF,
    ): array {
        $paiementProfId = $this->paiementProfTypeId();

        $base = Depense::query()
            ->whereHas('caisse', function (Builder $q) use ($user): void {
                $this->centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
            })
            ->when($typeFilter !== '', fn ($q) => $q->where('type_depense_id', (int) $typeFilter))
            // Tab split. When the type row is missing (never seeded), the
            // Paiements prof tab is simply empty and nothing is hidden from
            // the Dépenses tab.
            ->when(
                $paiementProfId !== null,
                fn ($q) => $scope === self::SCOPE_PAIEMENT_PROF
                    ? $q->where('type_depense_id', $paiementProfId)
                    : $q->where(fn ($sub) => $sub->whereNot('type_depense_id', $paiementProfId)->orWhereNull('type_depense_id')),
                fn ($q) => $scope === self::SCOPE_PAIEMENT_PROF ? $q->whereRaw('1 = 0') : $q,
            )
            ->when($caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $caisseFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_depense', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('date_depense', '<=', $dateTo))
            ->when($search !== '', function ($q) use ($search): void {
                $term = "%{$search}%";
                $q->where(function ($sub) use ($term): void {
                    $sub->where('reference', 'ilike', $term)
                        ->orWhere('description', 'ilike', $term)
                        ->orWhere('mots_cles', 'ilike', $term);
                });
            });

        $montantTotal = (clone $base)->sum('montant');

        $depenses = $base
            ->with(['typeDepense', 'caisse', 'agent'])
            ->withCount('media')
            ->latest()
            // Each tab paginates independently, so the Paiements prof tab
            // uses its own page query-string key.
            ->paginate($perPage, ['*'], $scope === self::SCOPE_PAIEMENT_PROF ? 'pageProf' : 'page')
            ->withQueryString();

        $depenses->through(fn (Depense $d): array => [
            'id' => $d->id,
            'reference' => $d->reference,
            'typeDepense' => $d->typeDepense?->nom,
            'typeDepenseId' => $d->type_depense_id,
            'caisse' => $d->caisse?->nom,
            'caisseId' => $d->caisse_id,
            'groupId' => $d->group_id,
            'montant' => number_format((float) $d->montant, 2, '.', ''),
            'methodePaiement' => $d->methode_paiement,
            'dateDepense' => $d->date_depense?->toDateString(),
            'referenceFacture' => $d->reference_facture,
            'description' => $d->description,
            'motsCles' => $d->mots_cles,
            'note' => $d->note,
            'agent' => $d->agent?->nomComplet(),
            'receiptsCount' => $d->media_count,
            'showUrl' => route('backoffice.depenses.show', $d),
        ]);

        return [
            'data' => $depenses,
            'montantTotal' => number_format((float) $montantTotal, 2, '.', ''),
        ];
    }

    /**
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function groupOptions(User $user): Collection
    {
        return Group::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get()
            ->map(fn (Group $g): array => ['id' => $g->id, 'nom' => $g->nom]);
    }

    /** id of the seeded "Paiement prof" type, or null when it isn't seeded. */
    public function paiementProfTypeId(): ?int
    {
        return TypeDepense::query()
            ->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)
            ->value('id');
    }

    /**
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function typeDepenseOptions(): Collection
    {
        return TypeDepense::query()
            ->where('statut', TypeDepense::STATUT_ACTIF)
            ->orderBy('nom')
            ->get()
            ->map(fn (TypeDepense $t): array => ['id' => $t->id, 'nom' => $t->nom]);
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
