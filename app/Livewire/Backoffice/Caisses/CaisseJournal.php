<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Caisses;

use App\Livewire\Backoffice\Concerns\WithCenterContext;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Encaissement;
use App\Models\Remboursement;
use App\Services\Authorization\CenterAccessService;
use App\Services\CaisseProvisioner;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Unified till journal — the « Ma caisse » / « Journal des transactions »
 * tabs of the Gestion de la caisse page.
 *
 * Merges the four money trails touching the tills in scope (encaissements,
 * dépenses, remboursements, transferts) into one chronological list, with
 * stat tiles (encaissements / dépenses / solde) and per-type totals.
 *
 * Read-only by design: every mutation stays on its own module (payments,
 * expenses, refunds, transfers) so the money invariants keep applying.
 *
 * scope = 'mine'  → tills whose responsable is the signed-in employee;
 * scope = 'all'   → every till the user may see (permission + active center).
 *
 * The merge happens in PHP over the date-filtered sets — fine at till scale
 * (a few thousand rows/year); revisit with a UNION query if it ever grows.
 */
final class CaisseJournal extends Component
{
    use AuthorizesRequests;
    use WithCenterContext;

    private const PER_PAGE = 10;

    public const TYPE_PAIEMENT = 'paiement';
    public const TYPE_DEPENSE = 'depense';
    public const TYPE_REMBOURSEMENT = 'remboursement';
    public const TYPE_TRANSFERT = 'transfert';

    /** 'mine' (own till) or 'all' (every accessible till). */
    public string $scope = 'mine';

    public string $typeFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $page = 1;

    public function mount(string $scope = 'mine'): void
    {
        $this->scope = $scope === 'all' ? 'all' : 'mine';
        $this->authorize('cash-registers.view');
    }

    public function updatedTypeFilter(): void
    {
        $this->page = 1;
    }

    public function updatedDateFrom(): void
    {
        $this->page = 1;
    }

    public function updatedDateTo(): void
    {
        $this->page = 1;
    }

    public function nextPage(): void
    {
        $this->page++;
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    /** @return array<int, int> */
    private function caisseIds(): array
    {
        if ($this->scope === 'mine') {
            $employee = auth()->user()?->employee;

            if ($employee === null) {
                return [];
            }

            // Self-healing: every employee owns exactly one till. Accounts
            // predating the auto-provisioning rule (or restored from old
            // dumps) get theirs on first visit — provisionFor() is idempotent.
            app(CaisseProvisioner::class)->provisionFor($employee);

            return $employee->caisses()->pluck('id')->all();
        }

        return Caisse::query()
            ->tap(fn ($q) => app(CenterAccessService::class)->scopeAccessibleCenters($q, auth()->user()))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->pluck('id')
            ->all();
    }

    /**
     * One normalized journal row per money movement.
     *
     * @param array<int, int> $ids
     */
    private function rows(array $ids): Collection
    {
        $rows = collect();

        $wants = fn (string $type): bool => $this->typeFilter === '' || $this->typeFilter === $type;

        if ($wants(self::TYPE_PAIEMENT)) {
            $rows = $rows->concat(
                Encaissement::query()->with(['student', 'agent'])
                    ->whereIn('caisse_id', $ids)
                    ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date_paiement', '>=', $this->dateFrom))
                    ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date_paiement', '<=', $this->dateTo))
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
                    ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date_depense', '>=', $this->dateFrom))
                    ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date_depense', '<=', $this->dateTo))
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
                    ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date_remboursement', '>=', $this->dateFrom))
                    ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date_remboursement', '<=', $this->dateTo))
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
                        'url' => null,
                    ]),
            );
        }

        if ($wants(self::TYPE_TRANSFERT)) {
            $rows = $rows->concat(
                CaisseTransfer::query()->with(['caisseSource', 'caisseDestination', 'requestedBy'])
                    ->where(fn ($q) => $q->whereIn('caisse_source_id', $ids)->orWhereIn('caisse_destination_id', $ids))
                    ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('date_transfert', '>=', $this->dateFrom))
                    ->when($this->dateTo !== '', fn ($q) => $q->whereDate('date_transfert', '<=', $this->dateTo))
                    ->get()
                    ->map(fn ($t) => [
                        'type' => self::TYPE_TRANSFERT,
                        'reference' => $t->reference,
                        'libelle' => ($t->caisseSource?->nom ?? '—').' → '.($t->caisseDestination?->nom ?? '—'),
                        'tiers' => $t->statut,
                        'montant' => (float) $t->montant,
                        // Outgoing from a till in scope reads as money out.
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

    public function render(): View
    {
        $ids = $this->caisseIds();

        // Tiles: overall totals for the tills in scope (not date-filtered).
        $totalEncaissements = (float) Encaissement::query()->whereIn('caisse_id', $ids)->sum('montant');
        $totalDepenses = (float) Depense::query()->whereIn('caisse_id', $ids)->sum('montant');
        $solde = (float) Caisse::query()->whereIn('id', $ids)->sum('solde');

        $rows = $this->rows($ids);

        // Per-type totals over the filtered journal (all pages).
        $totauxParType = $rows->groupBy('type')->map(fn ($g) => $g->sum('montant'));

        $lastPage = max(1, (int) ceil($rows->count() / self::PER_PAGE));
        $this->page = min($this->page, $lastPage);

        return view('livewire.backoffice.caisses.caisse-journal', [
            'caissesInScope' => Caisse::query()->whereIn('id', $ids)->with('responsable')->get(),
            'totalEncaissements' => $totalEncaissements,
            'totalDepenses' => $totalDepenses,
            'solde' => $solde,
            'totauxParType' => $totauxParType,
            'total' => $rows->count(),
            'lastPage' => $lastPage,
            'rows' => $rows->slice(($this->page - 1) * self::PER_PAGE, self::PER_PAGE),
        ]);
    }
}
