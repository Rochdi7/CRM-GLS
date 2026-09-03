<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\Remboursement;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Support\Access\HiddenAccount;
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
            // Centre scoping reads the refund's OWN etablissement_id — the
            // centre it belongs to — never the caisse's (03/09/2026). The
            // till paying out may be homed to another centre entirely: a
            // centre-4 student refunded from a centre-1 cashier's till was
            // then listed on NEITHER centre, and the cashier, seeing nothing
            // saved, refunded the same 300 DH a second time. Reach is still
            // checked, on the same column.
            ->where(function (Builder $q) use ($user): void {
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

    /**
     * The tills a refund may be paid out of: cash accounts (« Caissière » /
     * « Externe ») of the centres the user can reach, narrowed to the active
     * centre by the top-bar switcher.
     *
     * Added 03/09/2026 — the till used to be derived silently from the
     * ACTING employee (CaisseResolver::tillOf), which is why a Salé-homed
     * cashier refunding a Rabat student drained a Salé till with nothing on
     * screen to say so. The cashier now names the till, and therefore the
     * employee, the money actually leaves.
     *
     * Never a TPE/Chèque/Virement account: a refund is cash handed back
     * (CLAUDE.md §11). The one exception — reversing a bounced-cheque
     * payment — is still resolved server-side by CaisseResolver and needs no
     * dropdown entry.
     *
     * @return Collection<int, array{id:int, nom:string, solde:string}>
     */
    public function caisseOptions(User $user): Collection
    {
        return Caisse::query()
            ->whereIn('type', Caisse::TYPES_ESPECES)
            ->with('responsable')
            // The maintainer's till is never offered (HiddenAccount) — the
            // subquery drops global scopes, see CLAUDE.md §11.
            ->tap(fn ($q) => HiddenAccount::hideCaisses($q))
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get()
            ->map(fn (Caisse $c): array => [
                'id' => $c->id,
                // The responsable is what the cashier actually recognises;
                // the caisse's own nom is already the employee name for a
                // « Caissière » till, but an « Externe » safe is not.
                'nom' => $c->responsable !== null && $c->responsable->nomComplet() !== $c->nom
                    ? "{$c->nom} — {$c->responsable->nomComplet()}"
                    : $c->nom,
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
