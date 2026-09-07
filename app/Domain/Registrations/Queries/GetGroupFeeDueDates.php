<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Queries;

use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read-model for « Échéances en masse » — one group's students listed with,
 * for one chosen fee, the due date each of them currently carries.
 *
 * The screen exists because a due date is decided PER GROUP but stored PER
 * INSCRIPTION (`inscription_fees.date_echeance`, copied from
 * `group_frais.date_echeance` at enrolment). When a group's schedule slips,
 * the correction had to be made by reopening every student's inscription
 * modal one at a time — thirty students meant thirty modals. This lists them
 * side by side so one date can be applied to the ones that share it.
 *
 * Scope is the same funnel as every other list (CLAUDE.md §11): centre reach
 * via CenterAccessService, then the ACTIVE centre and ACTIVE année from the
 * top-bar switcher. The action re-applies it on write — this query only
 * decides what is offered, never what is allowed.
 *
 * Two deliberate choices:
 *  - **masked fee lines are excluded** (`masque_le`): a hidden line is not
 *    owed, and its money has been released as an avance (§11). Re-dating it
 *    would be editing a row the student's own screen does not show.
 *  - **every inscription statut is listable, not just Active.** Unlike
 *    « Gestion des recouvrements », this is not chasing money: a closed
 *    dossier's échéance stays a historical record the school may need to
 *    correct. The statut is shown on each row so the operator sees what they
 *    are about to touch, and the filter defaults to Active.
 */
final class GetGroupFeeDueDates
{
    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * The rows for one group + one fee. Empty when either is unset — the page
     * asks the operator to choose before showing anything.
     *
     * @return Collection<int, array{
     *     feeId:int, inscriptionId:int, reference:?string, studentNom:string,
     *     telephone:?string, statut:string, feeNom:string, montant:string,
     *     dateEcheance:?string, montantPaye:string, statutFrais:string
     * }>
     */
    public function __invoke(User $user, ?int $groupId, ?int $fraisId, string $statutFilter = ''): Collection
    {
        if ($groupId === null || $fraisId === null) {
            return collect();
        }

        return InscriptionFee::query()
            ->with(['inscription.student', 'frais'])
            // Paid total in the SAME query — the per-row montantPaye() fires
            // one SUM per line, which is exactly the N+1 §17 forbids in a
            // read-model (a group can hold forty students).
            ->withSum('encaissements', 'montant')
            ->whereNull('masque_le')
            ->where('frais_id', $fraisId)
            ->whereHas('inscription', function (Builder $q) use ($user, $groupId, $statutFilter): void {
                $q->where('group_id', $groupId)
                    ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
                    ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
                    ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
                    ->tap(fn ($q) => $this->scopeToActiveCenter($q));
            })
            ->get()
            ->map(function (InscriptionFee $fee): array {
                $student = $fee->inscription?->student;
                $paye = (float) ($fee->encaissements_sum_montant ?? 0);

                return [
                    'feeId' => $fee->id,
                    'inscriptionId' => (int) $fee->inscription_id,
                    'reference' => $fee->inscription?->reference,
                    'studentNom' => trim(($student?->nom ?? '').' '.($student?->prenom ?? '')) ?: '—',
                    'telephone' => $student?->telephone,
                    'statut' => (string) ($fee->inscription?->statut ?? '—'),
                    'feeNom' => (string) ($fee->nom ?? $fee->frais?->nom ?? '—'),
                    'montant' => number_format((float) $fee->montant, 2, '.', ''),
                    'dateEcheance' => $fee->date_echeance?->toDateString(),
                    'montantPaye' => number_format($paye, 2, '.', ''),
                    'statutFrais' => (string) $fee->statut,
                ];
            })
            // Sorted in PHP over a set already bounded by ONE group: the name
            // lives on the student, so ordering in SQL would need a join whose
            // only purpose is this display order.
            ->sortBy('studentNom', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Groups of the active centre + année the user may reach.
     *
     * @return array<int, array{value:int,label:string}>
     */
    public function groupOptions(User $user): array
    {
        return Group::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Group $g): array => ['value' => $g->id, 'label' => (string) $g->nom])
            ->all();
    }

    /**
     * The fees ACTUALLY carried by that group's inscriptions — not the whole
     * catalog. Offering a fee no student of the group holds would open an
     * empty table with nothing on screen explaining why.
     *
     * @return array<int, array{value:int,label:string}>
     */
    public function fraisOptionsForGroup(User $user, ?int $groupId): array
    {
        if ($groupId === null) {
            return [];
        }

        $ids = InscriptionFee::query()
            ->whereNull('masque_le')
            ->whereNotNull('frais_id')
            ->whereHas('inscription', function (Builder $q) use ($user, $groupId): void {
                $q->where('group_id', $groupId)
                    ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
                    ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
                    ->tap(fn ($q) => $this->scopeToActiveCenter($q));
            })
            ->distinct()
            ->pluck('frais_id')
            ->all();

        if ($ids === []) {
            return [];
        }

        return Frais::query()
            ->whereIn('id', $ids)
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Frais $f): array => ['value' => $f->id, 'label' => (string) $f->nom])
            ->all();
    }

    /** @return list<string> */
    public function statutOptions(): array
    {
        return Inscription::STATUTS;
    }

    private function scopeToActiveCenter(Builder $query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
