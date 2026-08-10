<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Queries;

use App\Models\Employee;
use App\Models\Group;
use App\Models\Salle;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;

/**
 * Select options for the Créneau (emploi du temps) grid filters and the
 * "Ajouter" modal — schedulable groups (not archived), teachers, rooms, all
 * scoped to the active center/year like GetSeanceFormOptions.
 */
final class GetCreneauFormOptions
{
    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return list<array{value: int, label: string, enseignantId: ?int, salleId: ?int}>
     */
    public function groups(User $user): array
    {
        return Group::query()
            ->whereIn('statut', [Group::STATUT_EN_INSCRIPTION, Group::STATUT_EN_FORMATION])
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q): mixed => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->orderBy('nom')
            ->get(['id', 'nom', 'niveau', 'enseignant_id', 'salle_id'])
            ->map(fn (Group $group): array => [
                'value' => $group->id,
                'label' => "{$group->nom} ({$group->niveau})",
                'enseignantId' => $group->enseignant_id,
                'salleId' => $group->salle_id,
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
     * @return list<array{value: int, label: string}>
     */
    public function salles(): array
    {
        return Salle::query()
            ->where('statut', Salle::STATUT_ACTIVE)
            ->tap(function ($q): void {
                $id = $this->context->etablissementId();
                if ($id !== null) {
                    $q->where('etablissement_id', $id);
                }
            })
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Salle $salle): array => ['value' => $salle->id, 'label' => $salle->nom])
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
