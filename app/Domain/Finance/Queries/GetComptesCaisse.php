<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\Depense;
use App\Models\Encaissement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the « Comptes de caisse » tab of Gestion de la caisse.
 *
 * Lists every account money sits in — of two different natures:
 *
 *  1. STORED accounts, rows of `caisses`:
 *     - "Caissière" — an employee's own till, created by CaisseProvisioner
 *       (EmployeeObserver), never by hand.
 *     - "Externe" — the ONLY kind a user creates here (a safe, an outside
 *       holder…). It has no payment method of its own, so its balance can
 *       only be a stored one.
 *     Their solde is the stored `caisses.solde`, maintained by CaisseLedger.
 *
 *  2. DERIVED rows, one per payment method (TPE / Chèque / Virement):
 *     these are NOT rows in any table — money reaches them through a
 *     payment's `methode`, not through a `caisse_id`. They are aggregated
 *     live from encaissements/depenses carrying that method, so they can
 *     never drift out of sync and nothing has to be provisioned or migrated
 *     when a payment is recorded. Espèces is deliberately excluded: cash
 *     physically lands in an employee's till, which is already the
 *     "Caissière" row — deriving it again would double-count the same money.
 *
 * ⚠ NOT center-scoped, unlike GetCaissesList: this tab is super-admin only
 * (`cash-accounts.*` is absent from every role in
 * PermissionRegistry::matrix()) and its whole point is the global view of
 * where the money is.
 */
final class GetComptesCaisse
{
    public const DEFAULT_PER_PAGE = 15;

    /**
     * Payment methods that get their own derived row.
     *
     * Espèces is absent on purpose — see the class docblock.
     *
     * @var list<string>
     */
    public const DERIVED_TYPES = [
        Encaissement::METHODE_TPE,
        Encaissement::METHODE_CHEQUE,
        Encaissement::METHODE_VIREMENT,
    ];

    /** The only type a user may create by hand. */
    public const CREATABLE_TYPES = [Caisse::TYPE_EXTERNE];

    /**
     * Every type the filter dropdown offers, in display order.
     *
     * @return list<string>
     */
    public static function allTypes(): array
    {
        return [Caisse::TYPE_CAISSIERE, ...self::DERIVED_TYPES, Caisse::TYPE_EXTERNE];
    }

    /**
     * Stored accounts and derived method rows, merged then paginated.
     *
     * ⚠ The merge happens in PHP because the two halves have genuinely
     * different shapes (table rows vs. GROUP BY aggregates). It stays cheap:
     * the derived half is at most 3 rows — two aggregate queries in total,
     * NOT a query per row — and the stored half is only the tills and Externe
     * accounts, which is bounded by the number of employees. If that ever
     * stops being true, the fix is a PostgreSQL UNION ALL with SQL-level
     * pagination (the same treatment CaisseController::journal() is flagged
     * for in PERFORMANCE_OPTIMIZATION_REPORT.md).
     */
    public function __invoke(
        string $search = '',
        string $typeFilter = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1,
    ): LengthAwarePaginator {
        $rows = $this->storedAccounts($search, $typeFilter)
            ->concat($this->derivedMethodRows($search, $typeFilter))
            ->values();

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values()->all(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /**
     * Whether the account still carries movements — used to refuse a delete
     * (money records are never deleted, so neither is the account they hang
     * off; see the Finance invariants in CLAUDE.md §11).
     */
    public function hasMovements(Caisse $caisse): bool
    {
        return $caisse->encaissements()->exists()
            || $caisse->depenses()->exists()
            || $caisse->remboursements()->exists()
            || $caisse->transfersSortants()->exists()
            || $caisse->transfersEntrants()->exists();
    }

    /**
     * Real `caisses` rows — employee tills and hand-created Externe accounts.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function storedAccounts(string $search, string $typeFilter): Collection
    {
        if ($typeFilter !== '' && in_array($typeFilter, self::DERIVED_TYPES, true)) {
            return collect();
        }

        return Caisse::query()
            ->with(['etablissement', 'responsable'])
            ->withSum('encaissements as encaissements_total', 'montant')
            ->withSum('depenses as depenses_total', 'montant')
            ->when($typeFilter !== '', fn ($q) => $q->where('type', $typeFilter))
            ->when($search !== '', fn ($q) => $q->where('nom', 'ilike', "%{$search}%"))
            ->latest()
            ->get()
            ->map(fn (Caisse $caisse): array => [
                'id' => $caisse->id,
                'nom' => $caisse->nom,
                'type' => $caisse->type,
                'centre' => $caisse->etablissement?->nom_centre,
                'responsable' => $caisse->responsable?->nomComplet(),
                'encaissements' => $this->money((float) ($caisse->encaissements_total ?? 0)),
                'depenses' => $this->money((float) ($caisse->depenses_total ?? 0)),
                // Stored, application-maintained balance (CaisseLedger) — NOT
                // recomputed from the two totals above, which would ignore
                // remboursements, transfers and the opening balance.
                'solde' => $this->money((float) $caisse->solde),
                'statut' => $caisse->statut,
                'dateAjout' => $caisse->created_at?->format('d/m/Y'),
                'derived' => false,
                'showUrl' => route('backoffice.caisses.show', $caisse),
            ]);
    }

    /**
     * One row per payment method, aggregated live. No table, no id — these
     * rows are a VIEW of the movements, so they are never editable or
     * deletable (the React panel hides those actions on `derived` rows and
     * the controller only ever resolves a real Caisse model).
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function derivedMethodRows(string $search, string $typeFilter): Collection
    {
        $types = collect(self::DERIVED_TYPES)
            ->when($typeFilter !== '', fn ($c) => $c->filter(fn (string $t) => $t === $typeFilter))
            ->when($search !== '', fn ($c) => $c->filter(
                fn (string $t) => str_contains(mb_strtolower($t), mb_strtolower($search)),
            ));

        if ($types->isEmpty()) {
            return collect();
        }

        // Two aggregate queries for the whole derived half, whatever the
        // number of methods — never one per row.
        $encaissements = Encaissement::query()
            ->selectRaw('methode, sum(montant) as total')
            ->whereIn('methode', $types->all())
            ->groupBy('methode')
            ->pluck('total', 'methode');

        $depenses = Depense::query()
            ->selectRaw('methode_paiement, sum(montant) as total')
            ->whereIn('methode_paiement', $types->all())
            ->groupBy('methode_paiement')
            ->pluck('total', 'methode_paiement');

        return $types->values()->map(function (string $methode) use ($encaissements, $depenses): array {
            $encaisse = (float) ($encaissements[$methode] ?? 0);
            $depense = (float) ($depenses[$methode] ?? 0);

            return [
                // No id: there is no row to point at. The React panel keys on
                // `type` for these and renders no row actions.
                'id' => null,
                'nom' => $methode,
                'type' => $methode,
                'centre' => null,
                'responsable' => null,
                'encaissements' => $this->money($encaisse),
                'depenses' => $this->money($depense),
                // Derived, so the balance IS the movements — recomputed on
                // every read, impossible to drift.
                'solde' => $this->money($encaisse - $depense),
                'statut' => Caisse::STATUT_ACTIVE,
                'dateAjout' => null,
                'derived' => true,
                'showUrl' => null,
            ];
        });
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
