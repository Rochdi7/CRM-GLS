<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\Presence;
use App\Models\Seance;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the Séances list — same center/year scoping recipe as
 * GetGroupsList (accessible centers ∩ active context center, active academic
 * year), search on the group name, newest session first.
 */
final class GetSeancesList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  array{search?: string, groupFilter?: string, statutFilter?: string, enseignantFilter?: string, dateFrom?: string, dateTo?: string}  $filters
     */
    public function __invoke(User $user, array $filters = [], int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $search = $filters['search'] ?? '';
        $groupFilter = $filters['groupFilter'] ?? '';
        $statutFilter = $filters['statutFilter'] ?? '';
        $enseignantFilter = $filters['enseignantFilter'] ?? '';
        $dateFrom = $filters['dateFrom'] ?? '';
        $dateTo = $filters['dateTo'] ?? '';

        $seances = Seance::query()
            ->with(['group:id,nom,niveau', 'enseignant:id,nom,prenom'])
            ->withCount([
                'presences',
                'presences as presents_count' => fn ($q) => $q->where('statut', Presence::STATUT_PRESENT),
                'presences as absents_count' => fn ($q) => $q->where('statut', Presence::STATUT_ABSENT),
            ])
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($groupFilter !== '', fn ($q) => $q->where('group_id', (int) $groupFilter))
            ->when($enseignantFilter !== '', fn ($q) => $q->where('enseignant_id', (int) $enseignantFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_seance', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('date_seance', '<=', $dateTo))
            ->when($search !== '', fn ($q) => $q->whereHas(
                'group',
                fn ($g) => $g->where('nom', 'ilike', "%{$search}%"),
            ))
            ->orderByDesc('date_seance')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $seances->through(fn (Seance $seance): array => [
            'id' => $seance->id,
            'dateSeance' => $seance->date_seance->toDateString(),
            'heureDebut' => $seance->heure_debut ? substr($seance->heure_debut, 0, 5) : null,
            'heureFin' => $seance->heure_fin ? substr($seance->heure_fin, 0, 5) : null,
            'groupId' => $seance->group_id,
            'groupNom' => $seance->group?->nom,
            'groupNiveau' => $seance->group?->niveau,
            'enseignant' => $seance->enseignant?->nomComplet(),
            'enseignantId' => $seance->enseignant_id,
            'statut' => $seance->statut,
            'note' => $seance->note,
            'presencesCount' => $seance->presences_count,
            'presentsCount' => $seance->presents_count,
            'absentsCount' => $seance->absents_count,
            'showUrl' => route('backoffice.seances.show', $seance),
        ]);

        return $seances;
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
