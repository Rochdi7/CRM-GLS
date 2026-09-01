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
            ->whereIn('statut', [Group::STATUT_EN_INSCRIPTION, Group::STATUT_EN_FORMATION])
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
     * Teachers selectable on this screen: the ACTIVE centre only.
     *
     * Reach follows the `employee_etablissement` pivot, not only
     * `employees.etablissement_id` (CLAUDE.md §16) — a teacher whose PRIMARY
     * centre is elsewhere but who is assigned to this one must stay
     * selectable, which is why this cannot use
     * CenterAccessService::scopeAccessibleCenters() (it matches the column
     * alone). Same rule the Groups form already applies
     * (GetGroupFormOptions::enseignants()).
     *
     * On « Tous les centres » (super-admin, etablissementId() === null) there
     * is no active centre to narrow to, so the list falls back to the centres
     * the user actually reaches, exactly like salles() below — never every
     * teacher of every branch.
     *
     * Reported 01/09/2026: « Saisir l'absence » in GLS Marrakech offered the
     * teachers of all seven branches in its Employé dropdown.
     *
     * @return list<array{value: int, label: string}>
     */
    public function enseignants(User $user): array
    {
        return Employee::query()
            ->where('categorie', Employee::CATEGORIE_ENSEIGNANT)
            ->where('statut', Employee::STATUT_ACTIF)
            ->tap(fn ($q) => $this->scopeEnseignantsToCenters($q, $user))
            ->orderBy('nom')
            ->get(['id', 'nom', 'prenom', 'etablissement_id'])
            ->map(fn (Employee $employee): array => [
                'value' => $employee->id,
                'label' => $employee->nomComplet(),
            ])
            ->all();
    }

    /**
     * Narrows a teacher query to the active centre, or — on « Tous les
     * centres » — to the centres the user reaches. Matches the primary
     * column OR the pivot; NULL-centre staff stay listed as global.
     */
    private function scopeEnseignantsToCenters($query, User $user): void
    {
        $active = $this->context->etablissementId();

        if ($active !== null) {
            $query->where(fn ($q) => $q->whereNull('etablissement_id')
                ->orWhere('etablissement_id', $active)
                ->orWhereHas('etablissements', fn ($e) => $e->where('etablissements.id', $active)));

            return;
        }

        if ($this->centerAccess->hasGlobalAccess($user)) {
            return;
        }

        $ids = $this->centerAccess->accessibleCenterIds($user);

        $query->where(fn ($q) => $q->whereNull('etablissement_id')
            ->orWhereIn('etablissement_id', $ids)
            ->orWhereHas('etablissements', fn ($e) => $e->whereIn('etablissements.id', $ids)));
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
