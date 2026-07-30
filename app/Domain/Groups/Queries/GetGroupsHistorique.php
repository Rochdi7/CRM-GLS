<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

use App\Models\GroupHistorique;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated archive listing — extracted verbatim from
 * GroupHistoriqueController::index() (same query, same eager loads, same
 * ordering, same page size). Read-only: rows only ever come from
 * Group::archiverCommeTermine(), never from this class.
 */
final class GetGroupsHistorique
{
    public function __invoke(int $perPage = 15): LengthAwarePaginator
    {
        $historiques = GroupHistorique::query()
            ->with(['group', 'enseignant', 'etablissement', 'anneeScolaire', 'archivedBy'])
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
}
