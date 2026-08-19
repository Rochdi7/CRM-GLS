<?php

declare(strict_types=1);

namespace App\Domain\Employees\Queries;

use App\Models\Employee;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the Employees list — extracted verbatim from
 * EmployeesIndex::render() (same eager loads, same ilike search columns,
 * same categorie filter, same center scoping, same `latest()` ordering,
 * same per-page options) so the React page and the legacy Livewire page stay
 * byte-for-byte behaviorally identical while both exist.
 */
final class GetEmployeesList
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
        string $categorieFilter = '',
        string $statutFilter = '',
        string $etablissementFilter = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $employees = Employee::query()
            // `media` eager-loaded for the avatar column, `user` for the
            // "Voir le compte" action (avoids N+1 on both).
            ->with(['etablissement', 'etablissements', 'media', 'user'])
            // Center scoping matches on ANY assigned center (pivot), not just
            // the primary column — an employee working in Marrakech + Rabat
            // is visible to admins of BOTH centers.
            ->tap(function ($q) use ($user): void {
                if ($this->centerAccess->hasGlobalAccess($user)) {
                    return;
                }

                $ids = $this->centerAccess->accessibleCenterIds($user);

                $q->where(function ($sub) use ($ids): void {
                    $sub->whereNull('etablissement_id')
                        ->orWhereIn('etablissement_id', $ids)
                        ->orWhereHas('etablissements', fn ($e) => $e->whereIn('etablissements.id', $ids));
                });
            })
            // Narrow to the center selected in the top-bar switcher.
            ->tap(function ($q): void {
                if (! $this->context->isAllCenters() && $this->context->etablissementId() !== null) {
                    $centreId = $this->context->etablissementId();

                    $q->where(function ($sub) use ($centreId): void {
                        $sub->where('etablissement_id', $centreId)
                            ->orWhereHas('etablissements', fn ($e) => $e->where('etablissements.id', $centreId));
                    });
                }
            })
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('nom', 'ilike', "%{$search}%")
                        ->orWhere('prenom', 'ilike', "%{$search}%")
                        ->orWhere('reference', 'ilike', "%{$search}%")
                        ->orWhere('adresse', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->when($categorieFilter !== '', fn ($q) => $q->where('categorie', $categorieFilter))
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($etablissementFilter !== '', function ($q) use ($etablissementFilter): void {
                $centreId = (int) $etablissementFilter;

                $q->where(function ($sub) use ($centreId): void {
                    $sub->where('etablissement_id', $centreId)
                        ->orWhereHas('etablissements', fn ($e) => $e->where('etablissements.id', $centreId));
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $employees->through(fn (Employee $employee): array => [
            'id' => $employee->id,
            'reference' => $employee->reference,
            'nom' => $employee->nom,
            'prenom' => $employee->prenom,
            'nomComplet' => $employee->nomComplet(),
            'sexe' => $employee->sexe,
            'categorie' => $employee->categorie,
            'statut' => $employee->statut,
            'telephone' => $employee->telephone,
            'whatsapp' => $employee->whatsapp,
            'email' => $employee->email,
            'adresse' => $employee->adresse,
            'note' => $employee->note,
            'dateNaissance' => $employee->date_naissance?->toDateString(),
            'dateEmbauche' => $employee->date_embauche?->toDateString(),
            'salaire' => $employee->salaire !== null ? (string) $employee->salaire : null,
            'etablissementId' => $employee->etablissement_id,
            'etablissement' => $employee->etablissement?->nom_centre,
            'etablissementIds' => $employee->etablissements->pluck('id')->all(),
            // Every assigned center, for the list's "Centre" column.
            'etablissementNoms' => $employee->etablissements->pluck('nom_centre')->all(),
            'userId' => $employee->user?->id,
            'username' => $employee->user?->username,
            'photoUrl' => $employee->getFirstMediaUrl('photo') ?: null,
            'photoThumbUrl' => $employee->getFirstMediaUrl('photo', 'thumb') ?: $employee->avatarUrl(),
        ]);

        return $employees;
    }
}
