<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Models\Inscription;
use App\Models\Student;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the Students list — extracted verbatim from
 * StudentsIndex::render() (same eager loads, same ilike search columns,
 * same niveau filter, same age sort, same center scoping, same per-page
 * options) so the React page and the legacy Livewire page stay
 * byte-for-byte behaviorally identical while both exist.
 */
final class GetStudentsList
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
        string $niveauFilter = '',
        string $sexeFilter = '',
        string $etablissementFilter = '',
        string $ageSort = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        string $referenceFilter = '',
        string $nomFilter = '',
        string $prenomFilter = '',
        string $telephoneFilter = '',
        string $inscriptionFilter = '',
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $students = Student::query()
            // `media` eager-loaded for the avatar column (avoids N+1).
            ->with(['etablissement', 'media'])
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            // Narrow to the center selected in the top-bar switcher.
            ->tap(function ($q): void {
                if (! $this->context->isAllCenters()) {
                    $q->where(fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $this->context->etablissementId()));
                }
            })
            // NO year scoping: a STUDENT has no academic year — only their
            // INSCRIPTIONS do. A person enrolled in 2025/2026 is the same
            // person in 2026/2027 and must stay findable (and re-enrollable)
            // whatever the top-bar year says. The year switcher governs
            // inscriptions, groups, séances and money — never the people.
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('nom', 'ilike', "%{$search}%")
                        ->orWhere('prenom', 'ilike', "%{$search}%")
                        ->orWhere('reference', 'ilike', "%{$search}%")
                        ->orWhere('cin', 'ilike', "%{$search}%")
                        ->orWhere('telephone', 'ilike', "%{$search}%");
                });
            })
            ->when($referenceFilter !== '', fn ($q) => $q->where('reference', 'ilike', "%{$referenceFilter}%"))
            ->when($nomFilter !== '', fn ($q) => $q->where('nom', 'ilike', "%{$nomFilter}%"))
            ->when($prenomFilter !== '', fn ($q) => $q->where('prenom', 'ilike', "%{$prenomFilter}%"))
            ->when($telephoneFilter !== '', fn ($q) => $q->where(fn ($sub) => $sub
                ->where('telephone', 'ilike', "%{$telephoneFilter}%")
                ->orWhere('whatsapp', 'ilike', "%{$telephoneFilter}%")))
            // État d'inscription — 'active' = at least one Active registration,
            // 'cancelled' = at least one Annulée/Archivée, 'none' = no registration.
            ->when($inscriptionFilter === 'active', fn ($q) => $q->whereHas(
                'inscriptions',
                fn ($i) => $i->where('statut', Inscription::STATUT_ACTIVE),
            ))
            ->when($inscriptionFilter === 'cancelled', fn ($q) => $q->whereHas(
                'inscriptions',
                fn ($i) => $i->whereIn('statut', [Inscription::STATUT_ANNULEE, Inscription::STATUT_ARCHIVEE]),
            ))
            ->when($inscriptionFilter === 'none', fn ($q) => $q->whereDoesntHave('inscriptions'))
            ->when($niveauFilter !== '', fn ($q) => $q->where('niveau', $niveauFilter))
            ->when($sexeFilter !== '', fn ($q) => $q->where('sexe', $sexeFilter))
            ->when($etablissementFilter !== '', fn ($q) => $q->where('etablissement_id', (int) $etablissementFilter))
            ->when(
                $ageSort !== '',
                // Age asc = youngest first = most recent birth date; unknown birth dates go last.
                fn ($q) => $q->orderByRaw('date_naissance IS NULL')
                    ->orderBy('date_naissance', $ageSort === 'asc' ? 'desc' : 'asc'),
                fn ($q) => $q->latest(),
            )
            ->paginate($perPage)
            ->withQueryString();

        $students->through(fn (Student $student): array => [
            'id' => $student->id,
            'reference' => $student->reference,
            'nomComplet' => $student->nomComplet(),
            'prenom' => $student->prenom,
            'nom' => $student->nom,
            'sexe' => $student->sexe,
            'dateNaissance' => $student->date_naissance?->toDateString(),
            'age' => $student->age(),
            'cin' => $student->cin,
            'telephone' => $student->telephone,
            'whatsapp' => $student->whatsapp,
            'email' => $student->email,
            'adresse' => $student->adresse,
            'niveau' => $student->niveau,
            'orientation' => $student->niveau ? $student->orientation() : null,
            'domaine' => $student->domaine,
            'examenType' => $student->examen_type,
            'etablissementId' => $student->etablissement_id,
            'etablissement' => $student->etablissement?->nom_centre,
            'parentNom' => $student->parent_nom,
            'parentRelation' => $student->parent_relation,
            'parentSexe' => $student->parent_sexe,
            'parentCin' => $student->parent_cin,
            'parentTelephone' => $student->parent_telephone,
            'parentWhatsapp' => $student->parent_whatsapp,
            'note' => $student->note,
            'photoUrl' => $student->getFirstMediaUrl('photo') ?: null,
            'photoThumbUrl' => $student->getFirstMediaUrl('photo', 'thumb') ?: $student->avatarUrl(),
        ]);

        return $students;
    }
}
