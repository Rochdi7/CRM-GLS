<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Support\Access\HiddenAccount;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the Caisses list — extracted verbatim from
 * CaissesIndex::render() (same eager loads, same center-scoping via
 * CenterAccessService + the active top-bar center, same center/status
 * filters, same search columns) so the React page and the legacy Livewire
 * tab stay behaviorally identical while both exist. Read-only: a caisse is
 * never created/edited/deleted by hand (CaisseProvisioner via
 * EmployeeObserver is the only writer) — this class has no mutation
 * counterpart, matching the Livewire component having zero action methods.
 */
final class GetCaissesList
{
    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
    ) {}

    public function __invoke(
        User $user,
        ?int $activeCenterId,
        string $search = '',
        string $etablissementFilter = '',
        string $statutFilter = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        $caisses = Caisse::query()
            ->with(['etablissement', 'responsable'])
            // EmployeeObserver provisions a till for EVERY employee, the
            // maintainer's hidden record included. That till never holds a
            // dirham, so it is filtered out of the finance screens with the
            // account it belongs to — the row itself is never deleted (money
            // accounts are permanent, CLAUDE.md §11).
            ->tap(fn ($q) => HiddenAccount::hideCaisses($q))
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q, $activeCenterId))
            ->when($etablissementFilter !== '', fn ($q) => $q->where('etablissement_id', (int) $etablissementFilter))
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('nom', 'ilike', "%{$search}%")
                    ->orWhereHas('responsable', fn ($r) => $r
                        ->where('nom', 'ilike', "%{$search}%")
                        ->orWhere('prenom', 'ilike', "%{$search}%"));
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $caisses->through(fn (Caisse $caisse): array => [
            'id' => $caisse->id,
            'nom' => $caisse->nom,
            'centre' => $caisse->etablissement?->nom_centre,
            'responsable' => $caisse->responsable?->nomComplet(),
            'solde' => number_format((float) $caisse->solde, 2, '.', ''),
            'statut' => $caisse->statut,
            'showUrl' => route('backoffice.caisses.show', $caisse),
        ]);

        return $caisses;
    }

    /**
     * @return Collection<int, array{id: int, nom: string}>
     */
    public function etablissementOptions(User $user): Collection
    {
        return Etablissement::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user, 'id'))
            ->orderBy('nom_centre')
            ->get()
            ->map(fn (Etablissement $e): array => ['id' => $e->id, 'nom' => $e->nom_centre]);
    }

    private function scopeToActiveCenter($query, ?int $activeCenterId): void
    {
        if ($activeCenterId === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $activeCenterId));
    }
}
