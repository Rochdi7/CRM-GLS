<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Queries;

use App\Models\Inscription;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the Inscriptions list — extracted verbatim from
 * InscriptionsIndex::render() (same eager loads/count, same center+year
 * scoping, same search columns, same status filter, same `latest()`
 * ordering, same per-page options) so the React page and the legacy
 * Livewire page stay byte-for-byte behaviorally identical while both exist.
 */
final class GetInscriptionsList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    public function __invoke(
        User $user,
        string $search = '',
        string $statutFilter = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $inscriptions = Inscription::query()
            ->with(['student', 'group'])
            ->withCount('fees')
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(function ($q): void {
                if (! $this->context->isAllCenters()) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $this->context->etablissementId()));
                }
            })
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('reference', 'ilike', "%{$search}%")
                        ->orWhereHas('student', fn ($s) => $s->where('nom', 'ilike', "%{$search}%")
                            ->orWhere('prenom', 'ilike', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $inscriptions->through(fn (Inscription $inscription): array => [
            'id' => $inscription->id,
            'reference' => $inscription->reference,
            // Raw ids so the edit modal preselects by id, not by matching
            // display labels (two same-named students used to be ambiguous —
            // Phase 12 UX fix).
            'studentId' => $inscription->student_id,
            'groupId' => $inscription->group_id,
            'student' => $inscription->student?->nomComplet(),
            'studentShowUrl' => $inscription->student ? route('backoffice.students.show', $inscription->student) : null,
            'groupe' => $inscription->group?->nom,
            'date' => $inscription->date_inscription?->format('d/m/Y'),
            'montantTotal' => $inscription->montant_total !== null ? number_format((float) $inscription->montant_total, 2, '.', '') : null,
            'feesCount' => $inscription->fees_count,
            'statut' => $inscription->statut,
            'showUrl' => route('backoffice.inscriptions.show', $inscription),
        ]);

        return $inscriptions;
    }
}
