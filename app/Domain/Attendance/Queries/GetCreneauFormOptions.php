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
     * Rooms bookable on this screen: the ACTIVE centre only.
     *
     * On « Tous les centres » (super-admin, etablissementId() === null) there
     * is no active centre to narrow to, so the list falls back to the centres
     * the user actually reaches (CenterAccessService) instead of every room of
     * every branch — a room is physical, and booking one belonging to another
     * branch is never valid. CreneauController::assertSalleDuCentre() enforces
     * the same rule server-side on save; this keeps the dropdown from offering
     * what that guard would reject.
     *
     * @return list<array{value: int, label: string}>
     */
    public function salles(User $user): array
    {
        return Salle::query()
            ->where('statut', Salle::STATUT_ACTIVE)
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
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
