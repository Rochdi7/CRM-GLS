<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Support\Access\HiddenAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the « Comptes de caisse » tab of Gestion de la caisse.
 *
 * Lists every account money sits in — ALL of them real `caisses` rows whose
 * balance is the stored, CaisseLedger-maintained `solde`:
 *
 *  - "Caissière" — an employee's own physical till (Espèces), created by
 *    CaisseProvisioner (EmployeeObserver), never by hand.
 *  - "Externe" — a safe / outside cash holder, the ONLY kind a user creates
 *    here.
 *  - "TPE" / "Chèque" / "Virement" — one account per centre, provisioned
 *    with the centre (EtablissementObserver) and credited only by payments
 *    carrying that method (CaisseResolver).
 *
 * A flat list, newest account first (like the legacy screen). Every dirham
 * is in exactly one row: nothing is derived on top of the stored balances
 * any more (the old DERIVED_TYPES aggregation counted a TPE payment twice —
 * once in the cashier's till, once in a live "TPE" line).
 *
 * ⚠ NOT center-scoped, unlike GetCaissesList: this tab is super-admin only
 * (`cash-accounts.*` is absent from every role in
 * PermissionRegistry::matrix()) and its whole point is the global view of
 * where the money is.
 */
final class GetComptesCaisse
{
    public const DEFAULT_PER_PAGE = 15;

    /** The only type a user may create by hand. */
    public const CREATABLE_TYPES = [Caisse::TYPE_EXTERNE];

    /**
     * Every type the filter dropdown offers, in display order.
     *
     * @return list<string>
     */
    public static function allTypes(): array
    {
        return [Caisse::TYPE_CAISSIERE, ...Caisse::TYPES_METHODE, Caisse::TYPE_EXTERNE];
    }

    public function __invoke(
        string $search = '',
        string $typeFilter = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1,
    ): LengthAwarePaginator {
        $rows = $this->baseQuery($search, $typeFilter)
            ->latest()
            ->orderBy('nom')
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
                // Method accounts are provisioned, not managed: no edit/delete.
                'compteMethode' => $caisse->isCompteMethode(),
                'showUrl' => route('backoffice.caisses.show', $caisse),
            ]);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values()->all(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => request()->query()],
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

    /** @return Builder<Caisse> */
    private function baseQuery(string $search, string $typeFilter): Builder
    {
        return Caisse::query()
            ->with(['etablissement', 'responsable'])
            // Same rule as GetCaissesList: the maintainer's auto-provisioned
            // till is hidden along with the account (HiddenAccount).
            ->tap(fn ($q) => HiddenAccount::hideCaisses($q))
            ->withSum('encaissements as encaissements_total', 'montant')
            ->withSum('depenses as depenses_total', 'montant')
            ->when($typeFilter !== '', fn ($q) => $q->where('type', $typeFilter))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('nom', 'ilike', "%{$search}%")
                    ->orWhereHas('etablissement', fn ($e) => $e->where('nom_centre', 'ilike', "%{$search}%"));
            }));
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
