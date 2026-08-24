<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

use App\Models\GroupHistorique;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated archive listing — extracted verbatim from
 * GroupHistoriqueController::index() (same query, same eager loads, same
 * ordering, same page size). Read-only: rows only ever come from
 * Group::archiverCommeTermine(), never from this class.
 *
 * Scoped like every other list (§11, audit 24/08/2026): centre access +
 * active context centre + active context year. It previously listed every
 * centre's archives to everyone — while the UI hid the Centre column under
 * a locked context, so foreign rows showed with no visual clue.
 */
final class GetGroupsHistorique
{
    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $historiques = GroupHistorique::query()
            ->with(['group', 'enseignant', 'etablissement', 'anneeScolaire', 'archivedBy'])
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->orderByDesc('archived_at')
            ->paginate($perPage);

        $historiques->through(fn (GroupHistorique $historique): array => [
            'id' => $historique->id,
            'nom' => $historique->nom,
            'niveau' => $historique->niveau,
            'enseignant' => $historique->enseignant?->nomComplet(),
            'centre' => $historique->etablissement?->nom_centre,
            'anneeScolaire' => $historique->anneeScolaire?->nom,
            'nombreEtudiants' => $historique->nombre_etudiants_final,
            'dateDebutFormation' => $historique->date_debut_formation?->format('d/m/Y'),
            'dateFinFormation' => $historique->date_fin_formation?->format('d/m/Y'),
            'archivedAt' => $historique->archived_at?->format('d/m/Y H:i'),
            'archivedBy' => $historique->archivedBy?->nomComplet(),
            'groupShowUrl' => $historique->group ? route('backoffice.groups.show', $historique->group) : null,
        ]);

        return $historiques;
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
