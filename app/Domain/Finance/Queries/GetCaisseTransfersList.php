<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the Caisse Transfers list — extracted verbatim from
 * CaisseTransfersIndex::render() (same center-scoping through the SOURCE
 * till, same status/source-till/search filters, same statutCounts tab
 * badges, same `currentEmployeeId` prop used to hide the Validate action on
 * your own request).
 */
final class GetCaisseTransfersList
{
    public const DEFAULT_PER_PAGE = 15;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(
        User $user,
        string $search = '',
        string $statutFilter = CaisseTransfer::STATUT_EN_ATTENTE,
        string $caisseFilter = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        $currentEmployeeId = $user->employee?->id;

        $transfers = CaisseTransfer::query()
            ->with(['caisseSource', 'caisseDestination', 'requestedBy', 'validatedBy'])
            ->whereHas('caisseSource', function (Builder $q) use ($user): void {
                $this->centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
            })
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($caisseFilter !== '', fn ($q) => $q->where('caisse_source_id', (int) $caisseFilter))
            ->when($search !== '', function ($q) use ($search): void {
                $term = "%{$search}%";
                $q->where(function ($sub) use ($term): void {
                    $sub->where('reference', 'ilike', $term)->orWhere('note', 'ilike', $term);
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $transfers->through(fn (CaisseTransfer $t): array => [
            'id' => $t->id,
            'reference' => $t->reference,
            'caisseSource' => $t->caisseSource?->nom,
            'caisseSourceId' => $t->caisse_source_id,
            'caisseDestination' => $t->caisseDestination?->nom,
            'montant' => number_format((float) $t->montant, 2, '.', ''),
            'dateTransfert' => $t->date_transfert?->toDateTimeString(),
            'statut' => $t->statut,
            'requestedBy' => $t->requestedBy?->nomComplet(),
            'requestedById' => $t->requested_by,
            'validatedBy' => $t->validatedBy?->nomComplet(),
            'note' => $t->note,
            'isPending' => $t->statut === CaisseTransfer::STATUT_EN_ATTENTE,
            'showUrl' => route('backoffice.caisse-transfers.show', $t),
        ]);

        return $transfers;
    }

    /**
     * @return Collection<string, int>
     */
    public function statutCounts(User $user): Collection
    {
        return CaisseTransfer::query()
            ->whereHas('caisseSource', function (Builder $q) use ($user): void {
                $this->centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
            })
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');
    }

    /**
     * @return Collection<int, array{id:int, nom:string, solde:string}>
     */
    public function caisseOptions(User $user): Collection
    {
        return Caisse::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get()
            ->map(fn (Caisse $c): array => [
                'id' => $c->id,
                'nom' => $c->nom,
                'solde' => number_format((float) $c->solde, 2, '.', ''),
            ]);
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
