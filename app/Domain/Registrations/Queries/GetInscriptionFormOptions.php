<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Queries;

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
     * @return Collection<int, array{id: int, label: string}>
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
            ->map(fn (Group $g): array => ['id' => $g->id, 'label' => "{$g->nom} — {$g->niveau}"]);
    }
}
