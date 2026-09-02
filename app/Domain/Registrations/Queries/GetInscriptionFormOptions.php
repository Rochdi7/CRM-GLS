<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Queries;

use App\Domain\Settings\Support\FraisEcheanceResolver;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Collection;

/**
 * Options for the Inscriptions create/edit form — extracted verbatim from
 * InscriptionsIndex::students()/groups() computed properties (same center
 * scoping for students, same center+active-year scoping for groups).
 */
final class GetInscriptionFormOptions
{
    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return Collection<int, array{id: int, label: string}>
     */
    public function students($user): Collection
    {
        return Student::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(function ($q): void {
                if (! $this->context->isAllCenters()) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $this->context->etablissementId()));
                }
            })
            ->orderBy('nom')
            ->get()
            ->map(fn (Student $s): array => ['id' => $s->id, 'label' => "{$s->nomComplet()} ({$s->reference})"]);
    }

    /**
     * `statut` rides along for the « Modification du groupe » modal, whose
     * dropdown (and row action) is limited to groups still « En inscription »
     * — UI convenience only, ModifierGroupeInscription re-checks it.
     *
     * @return Collection<int, array{id: int, label: string, statut: string}>
     */
    public function groups($user): Collection
    {
        return Group::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(function ($q): void {
                if (! $this->context->isAllCenters()) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $this->context->etablissementId()));
                }
            })
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->orderBy('nom')
            ->get()
            ->map(fn (Group $g): array => ['id' => $g->id, 'label' => "{$g->nom} — {$g->niveau}", 'statut' => $g->statut]);
    }

    /**
     * Target groups for the « Changement de groupe » modal ONLY — the same
     * centre scoping as groups(), but WITHOUT the active-year filter, so a
     * student can be moved from a 2025/2026 group into a 2026/2027 one
     * (asked for on 02/09/2026: a mid-year switch that lands in the next
     * academic year is a normal re-enrollment, not a data error).
     *
     * Every other group dropdown on the page (the list filter, the
     * create/edit form, « Modification du groupe ») keeps groups() and its
     * year window — only this one flow deliberately crosses years, and
     * ChangerGroupeInscription inherits the TARGET group's année on the new
     * inscription, so the successor row is filed under the year the student
     * actually joins.
     *
     * `anneeScolaireId` + `anneeLabel` ride along so the modal can group the
     * options behind an « Année scolaire » selector.
     *
     * @return Collection<int, array{id: int, label: string, statut: string, anneeScolaireId: int|null, anneeLabel: string|null}>
     */
    public function changeGroupGroups($user): Collection
    {
        return Group::query()
            ->with('anneeScolaire:id,nom')
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(function ($q): void {
                if (! $this->context->isAllCenters()) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $this->context->etablissementId()));
                }
            })
            ->orderBy('nom')
            ->get()
            ->map(fn (Group $g): array => [
                'id' => $g->id,
                'label' => "{$g->nom} — {$g->niveau}",
                'statut' => $g->statut,
                'anneeScolaireId' => $g->annee_scolaire_id,
                'anneeLabel' => $g->anneeScolaire?->nom,
            ]);
    }

    /**
     * Academic years that actually carry a reachable group — the « Année
     * scolaire » selector of the change-group modal. Derived from
     * changeGroupGroups() rather than queried on its own so the selector can
     * never offer a year whose groups the user cannot pick.
     *
     * @param  Collection<int, array{anneeScolaireId: int|null, anneeLabel: string|null}>  $groups
     * @return Collection<int, array{id: int, label: string}>
     */
    public function anneesScolairesFromGroups(Collection $groups): Collection
    {
        return $groups
            ->filter(fn (array $g): bool => $g['anneeScolaireId'] !== null)
            ->unique('anneeScolaireId')
            ->sortByDesc('anneeLabel')
            ->values()
            ->map(fn (array $g): array => ['id' => (int) $g['anneeScolaireId'], 'label' => (string) $g['anneeLabel']]);
    }

    /**
     * Active catalog fees — feeds the edit modal's "Ajouter un frais"
     * picker, so a fee not originally assigned to the group can still be
     * added to a single inscription after the fact.
     *
     * @return Collection<int, array{id: int, label: string}>
     */
    public function frais(): Collection
    {
        return Frais::query()
            ->where('statut', Frais::STATUT_ACTIF)
            ->orderBy('nom')
            ->get()
            // Same teaching-calendar order as the group fee table — see
            // GetGroupFormOptions::fraisCatalog().
            ->sortBy([
                fn (Frais $a, Frais $b): int => FraisEcheanceResolver::ordreFromNom($a->nom)
                    <=> FraisEcheanceResolver::ordreFromNom($b->nom),
                fn (Frais $a, Frais $b): int => strcmp($a->nom, $b->nom),
            ])
            ->values()
            ->map(fn (Frais $f): array => [
                'id' => $f->id,
                'label' => $f->nom,
                // Catalog fallback when the inscription's group does not
                // assign this fee — a re-added line must never start at 0.
                'montantDefaut' => number_format($f->montantPourCentre($this->context->etablissementId()), 2, '.', ''),
            ]);
    }
}
