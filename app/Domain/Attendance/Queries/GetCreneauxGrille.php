<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\Creneau;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Collection;

/**
 * Read-model for the "Emploi du temps" weekly grid — every créneau matching
 * the filters, scoped to the active center/year the same way Séances/Groups
 * are. The React page buckets rows by (jour_semaine, heure_debut) itself;
 * this query just returns the flat, already-authorized list.
 */
final class GetCreneauxGrille
{
    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  array{groupFilter?: string, enseignantFilter?: string, salleFilter?: string, jourFilter?: string}  $filters
     * @return Collection<int, array{
     *     id: int, groupId: int, groupNom: string, groupNiveau: ?string,
     *     jourSemaine: int, heureDebut: string, heureFin: string,
     *     enseignant: ?string, enseignantId: ?int, salle: ?string, salleId: ?int,
     * }>
     */
    public function __invoke(User $user, array $filters = []): Collection
    {
        $groupFilter = $filters['groupFilter'] ?? '';
        $enseignantFilter = $filters['enseignantFilter'] ?? '';
        $salleFilter = $filters['salleFilter'] ?? '';
        $jourFilter = $filters['jourFilter'] ?? '';

        return Creneau::query()
            ->with(['group:id,nom,niveau,etablissement_id,annee_scolaire_id', 'enseignant:id,nom,prenom', 'salle:id,nom'])
            ->whereHas('group', function ($q) use ($user): void {
                $this->centerAccess->scopeAccessibleCenters($q, $user);
                $this->scopeToActiveCenter($q);
                if ($this->context->anneeScolaireId()) {
                    $q->where('annee_scolaire_id', $this->context->anneeScolaireId());
                }
            })
            ->when($groupFilter !== '', fn ($q) => $q->where('group_id', (int) $groupFilter))
            ->when($enseignantFilter !== '', fn ($q) => $q->where('enseignant_id', (int) $enseignantFilter))
            ->when($salleFilter !== '', fn ($q) => $q->where('salle_id', (int) $salleFilter))
            ->when($jourFilter !== '', fn ($q) => $q->where('jour_semaine', (int) $jourFilter))
            ->orderBy('jour_semaine')
            ->orderBy('heure_debut')
            ->get()
            ->map(fn (Creneau $creneau): array => [
                'id' => $creneau->id,
                'groupId' => $creneau->group_id,
                'groupNom' => $creneau->group?->nom ?? '—',
                'groupNiveau' => $creneau->group?->niveau,
                'jourSemaine' => $creneau->jour_semaine,
                'heureDebut' => substr((string) $creneau->heure_debut, 0, 5),
                'heureFin' => substr((string) $creneau->heure_fin, 0, 5),
                'enseignant' => $creneau->enseignant?->nomComplet(),
                'enseignantId' => $creneau->enseignant_id,
                'salle' => $creneau->salle?->nom,
                'salleId' => $creneau->salle_id,
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
