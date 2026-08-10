<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\Employee;
use App\Models\Group;
use App\Models\Seance;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;

/**
 * Select options for the séance modal: schedulable groups (not archived,
 * same center/year scope as the list) and teachers.
 */
final class GetSeanceFormOptions
{
    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return list<array{value: int, label: string, enseignantId: ?int}>
     */
    public function groups(User $user): array
    {
        return Group::query()
            ->whereIn('statut', [Group::STATUT_PRE_INSCRIPTION, Group::STATUT_EN_FORMATION])
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->orderBy('nom')
            ->get(['id', 'nom', 'niveau', 'enseignant_id'])
            ->map(fn (Group $group): array => [
                'value' => $group->id,
                'label' => "{$group->nom} ({$group->niveau})",
                // Lets the modal pre-select the group's own teacher.
                'enseignantId' => $group->enseignant_id,
            ])
            ->all();
    }

    /**
     * @return list<array{value: int, label: string}>
     */
    public function enseignants(): array
    {
        return Employee::query()
            ->where('categorie', Employee::CATEGORIE_ENSEIGNANT)
            ->where('statut', Employee::STATUT_ACTIF)
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom'])
            ->map(fn (Employee $employee): array => [
                'value' => $employee->id,
                'label' => $employee->nomComplet(),
            ])
            ->all();
    }

    /**
     * Séance picker of the fiche de présence (Show page): the séances the
     * user may see for a given day, optionally narrowed to one teacher.
     * Label mirrors the design: "Enseignant - Groupe - de HH:MM à HH:MM - niveau".
     *
     * @return list<array{value: int, label: string}>
     */
    public function seancesFor(User $user, string $date, ?int $enseignantId): array
    {
        return Seance::query()
            ->with(['group:id,nom,niveau', 'enseignant:id,nom,prenom'])
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->whereDate('date_seance', $date)
            ->when($enseignantId, fn ($q, $id) => $q->where('enseignant_id', $id))
            ->orderBy('heure_debut')
            ->orderBy('id')
            ->get()
            ->map(function (Seance $seance): array {
                $parts = array_filter([
                    $seance->enseignant?->nomComplet(),
                    $seance->group?->nom,
                    $seance->heure_debut
                        ? 'de '.substr($seance->heure_debut, 0, 5).($seance->heure_fin ? ' à '.substr($seance->heure_fin, 0, 5) : '')
                        : null,
                    $seance->group?->niveau,
                ]);

                return [
                    'value' => $seance->id,
                    'label' => implode(' - ', $parts),
                ];
            })
            ->all();
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
