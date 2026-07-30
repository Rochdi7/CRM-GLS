<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

use App\Models\Group;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the Groups list — extracted verbatim from
 * GroupsIndex::render() (same eager loads/counts, same center/year scoping,
 * same status filter, same search column, same `latest()` ordering, same
 * per-status tab counts) so the React page and the legacy Livewire page stay
 * byte-for-byte behaviorally identical while both exist.
 */
final class GetGroupsList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(
        User $user,
        string $search = '',
        string $statutFilter = Group::STATUT_EN_FORMATION,
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $groups = Group::query()
            ->with(['enseignant', 'frais'])
            ->withCount(['inscriptions', 'frais'])
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($search !== '', fn ($q) => $q->where('nom', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $groups->through(fn (Group $group): array => [
            'id' => $group->id,
            'nom' => $group->nom,
            'niveau' => $group->niveau,
            'enseignant' => $group->enseignant?->nomComplet(),
            'enseignantId' => $group->enseignant_id,
            'dateDebutFormation' => $group->date_debut_formation?->toDateString(),
            'dateFinFormation' => $group->date_fin_formation?->toDateString(),
            'statut' => $group->statut,
            'inscriptionsCount' => $group->inscriptions_count,
            'fraisCount' => $group->frais_count,
            'showUrl' => route('backoffice.groups.show', $group),
            // Keyed by frais_id so the edit modal can prefill the fee-lines
            // table without a second request — same data Group::with('frais')
            // already gives the Livewire component's edit().
            'fraisLignes' => $group->frais->mapWithKeys(fn ($fee): array => [
                $fee->id => [
                    'montant' => (string) $fee->pivot->montant,
                    'dateEcheance' => $fee->pivot->date_echeance ?: '',
                    'classification' => $fee->pivot->classification ?: '',
                ],
            ])->all(),
        ]);

        return $groups;
    }

    /**
     * Per-status counts for the tab badges (same center/year scope, ignores search).
     *
     * @return Collection<string, int>
     */
    public function statutCounts(User $user): Collection
    {
        return Group::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');
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
