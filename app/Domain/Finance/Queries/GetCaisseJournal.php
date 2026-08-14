<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Encaissement;
use App\Models\Remboursement;
use App\Services\Authorization\CenterAccessService;
use App\Services\CaisseProvisioner;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Collection;

/**
 * Read-model for the unified till journal ("Ma caisse" / "Journal des
 * transactions" tabs) — extracted verbatim from CaisseJournal::render()
 * (docs/phase-10-finance-mapping.md Q4: ported as-is, preserving the exact
 * current PHP-merge/sort/slice() pagination semantics — the confirmed
 * performance bottleneck is preserved, not fixed, in this phase; see
 * PERFORMANCE_AUDIT.md/PERFORMANCE_OPTIMIZATION_REPORT.md).
 *
 * Merges 4 money trails (encaissements, depenses, remboursements,
 * transferts) into one chronological Collection per till scope
 * ('mine'|'all'), same as the Livewire component's own `rows()`/`render()`.
 */
final class GetCaisseJournal
{
    public const PER_PAGE = 10;

    public const TYPE_PAIEMENT = 'paiement';

    public const TYPE_DEPENSE = 'depense';

    public const TYPE_REMBOURSEMENT = 'remboursement';

    public const TYPE_TRANSFERT = 'transfert';

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
        private readonly CaisseProvisioner $provisioner,
    ) {}

    /**
     * @return array{
     *     caissesInScope: Collection<int, array{id:int, nom:string}>,
     *     totalEncaissements: string, totalDepenses: string, solde: string,
     *     totauxParType: array<string, string>, total: int, lastPage: int,
     *     page: int, rows: Collection<int, array<string, mixed>>,
     * }
     */
    public function __invoke(
        \App\Models\User $user,
        string $scope,
        string $typeFilter,
        string $dateFrom,
        string $dateTo,
        int $page,
    ): array {
        $scope = $scope === 'all' ? 'all' : 'mine';

        if ($scope === 'mine') {
            $employee = $user->employee;

            if ($employee !== null) {
                $this->provisioner->provisionFor($employee);
            }
        }

        $ids = $this->caisseIds($user, $scope);

        $totalEncaissements = (float) Encaissement::query()->whereIn('caisse_id', $ids)->sum('montant');
        $totalDepenses = (float) Depense::query()->whereIn('caisse_id', $ids)->sum('montant');
        // Refunds decrement the till exactly like a dépense
        // (EnregistrerRemboursement), but they are their own outflow and must
        // not be folded into the "Dépenses" KPI — without this total the page
        // contradicts itself: the solde drops while every displayed total
        // stays put, which reads as "the caisse didn't record the refund".
        $totalRemboursements = (float) Remboursement::query()->whereIn('caisse_id', $ids)->sum('montant');
        $solde = (float) Caisse::query()->whereIn('id', $ids)->sum('solde');

        $rows = $this->rows($ids, $typeFilter, $dateFrom, $dateTo);

        $totauxParType = $rows->groupBy('type')->map(fn ($g) => number_format((float) $g->sum('montant'), 2, '.', ''));

        $lastPage = max(1, (int) ceil($rows->count() / self::PER_PAGE));
        $page = min(max(1, $page), $lastPage);

        return [
            'caissesInScope' => Caisse::query()->whereIn('id', $ids)->with('responsable')->get()
                ->map(fn (Caisse $c): array => ['id' => $c->id, 'nom' => $c->nom]),
            'totalEncaissements' => number_format($totalEncaissements, 2, '.', ''),
            'totalDepenses' => number_format($totalDepenses, 2, '.', ''),
            'totalRemboursements' => number_format($totalRemboursements, 2, '.', ''),
            'solde' => number_format($solde, 2, '.', ''),
            'totauxParType' => $totauxParType->all(),
            'total' => $rows->count(),
            'lastPage' => $lastPage,
            'page' => $page,
            'rows' => $rows->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values()->map(fn (array $row): array => [
                ...$row,
                'montant' => number_format((float) $row['montant'], 2, '.', ''),
                'date' => $row['date']?->format('d/m/Y'),
            ]),
        ];
    }

    /** @return array<int, int> */
    private function caisseIds(\App\Models\User $user, string $scope): array
    {
        if ($scope === 'mine') {
            $employee = $user->employee;

            return $employee === null ? [] : $employee->caisses()->pluck('id')->all();
        }

        return Caisse::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function rows(array $ids, string $typeFilter, string $dateFrom, string $dateTo): Collection
    {
        $rows = collect();
        $wants = fn (string $type): bool => $typeFilter === '' || $typeFilter === $type;

        if ($wants(self::TYPE_PAIEMENT)) {
            $rows = $rows->concat(
                Encaissement::query()->with(['student', 'agent'])
                    ->whereIn('caisse_id', $ids)
                    ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_paiement', '>=', $dateFrom))
                    ->when($dateTo !== '', fn ($q) => $q->whereDate('date_paiement', '<=', $dateTo))
                    ->get()
                    ->map(fn ($e) => [
                        'type' => self::TYPE_PAIEMENT,
                        'reference' => $e->reference,
                        'libelle' => __('Payment received'),
                        'tiers' => $e->student?->nomComplet(),
                        'montant' => (float) $e->montant,
                        'sens' => 1,
                        'date' => $e->date_paiement,
                        'note' => $e->note,
                        'agent' => $e->agent?->nomComplet(),
                        'url' => route('backoffice.encaissements.show', $e),
                    ]),
            );
        }

        if ($wants(self::TYPE_DEPENSE)) {
            $rows = $rows->concat(
                Depense::query()->with(['typeDepense', 'agent'])
                    ->whereIn('caisse_id', $ids)
                    ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_depense', '>=', $dateFrom))
                    ->when($dateTo !== '', fn ($q) => $q->whereDate('date_depense', '<=', $dateTo))
                    ->get()
                    ->map(fn ($d) => [
                        'type' => self::TYPE_DEPENSE,
                        'reference' => $d->reference,
                        'libelle' => $d->typeDepense?->nom ?? __('Expense'),
                        'tiers' => $d->description,
                        'montant' => (float) $d->montant,
                        'sens' => -1,
                        'date' => $d->date_depense,
                        'note' => $d->note,
                        'agent' => $d->agent?->nomComplet(),
                        'url' => route('backoffice.depenses.show', $d),
                    ]),
            );
        }

        if ($wants(self::TYPE_REMBOURSEMENT)) {
            $rows = $rows->concat(
                Remboursement::query()->with(['beneficiaire', 'agent'])
                    ->whereIn('caisse_id', $ids)
                    ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_remboursement', '>=', $dateFrom))
                    ->when($dateTo !== '', fn ($q) => $q->whereDate('date_remboursement', '<=', $dateTo))
                    ->get()
                    ->map(fn ($r) => [
                        'type' => self::TYPE_REMBOURSEMENT,
                        'reference' => $r->reference,
                        'libelle' => $r->motif ?: __('Refund'),
                        'tiers' => $r->beneficiaire?->nomComplet(),
                        'montant' => (float) $r->montant,
                        'sens' => -1,
                        'date' => $r->date_remboursement,
                        'note' => $r->note,
                        'agent' => $r->agent?->nomComplet(),
                        // No detail page exists anywhere for Remboursements
                        // (docs/phase-10-finance-mapping.md Q2: preserved).
                        'url' => null,
                    ]),
            );
        }

        if ($wants(self::TYPE_TRANSFERT)) {
            $rows = $rows->concat(
                CaisseTransfer::query()->with(['caisseSource', 'caisseDestination', 'requestedBy'])
                    ->where(fn ($q) => $q->whereIn('caisse_source_id', $ids)->orWhereIn('caisse_destination_id', $ids))
                    ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_transfert', '>=', $dateFrom))
                    ->when($dateTo !== '', fn ($q) => $q->whereDate('date_transfert', '<=', $dateTo))
                    ->get()
                    ->map(fn ($t) => [
                        'type' => self::TYPE_TRANSFERT,
                        'reference' => $t->reference,
                        'libelle' => ($t->caisseSource?->nom ?? '—').' → '.($t->caisseDestination?->nom ?? '—'),
                        'tiers' => $t->statut,
                        'montant' => (float) $t->montant,
                        'sens' => in_array($t->caisse_source_id, $ids, true) ? -1 : 1,
                        'date' => $t->date_transfert,
                        'note' => $t->note,
                        'agent' => $t->requestedBy?->nomComplet(),
                        'url' => route('backoffice.caisse-transfers.show', $t),
                    ]),
            );
        }

        return $rows->sortByDesc(fn ($row) => $row['date']?->timestamp ?? 0)->values();
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
