<?php

declare(strict_types=1);

namespace App\Domain\Employees\Queries;

use App\Models\User;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the Users list — extracted verbatim from
 * UsersIndex::render() (App\Livewire\Backoffice\Users\UsersIndex): same
 * eager loads, same ilike search columns, same center-aware visibility
 * (accounts without an Employee profile stay visible in every center), same
 * `orderBy('name')` ordering, same per-page options — so the React page and
 * the legacy Livewire page stay byte-for-byte behaviorally identical while
 * both exist.
 *
 * Placed under the Employees domain module (not a dedicated `Users/` Domain
 * folder — none is reserved for it in PROJECT_INVENTORY.md §11, and Users
 * are produced exclusively by EmployeeObserver, so this read-model sits next
 * to GetEmployeesList as the closest "people management" precedent).
 */
final class GetUsersList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(private readonly CurrentContext $context) {}

    public function __invoke(
        string $search = '',
        int $perPage = self::DEFAULT_PER_PAGE,
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $users = User::query()
            ->with(['roles', 'employee.etablissement'])
            // Follow the active center via the employee profile; accounts
            // without an employee (pure admin logins) stay visible everywhere.
            ->when($this->context->etablissementId(), function ($query, int $centreId): void {
                $query->where(function ($q) use ($centreId): void {
                    $q->whereDoesntHave('employee')
                        // An employee may work in SEVERAL centers — match on the
                        // pivot as well as the primary column, so a shared
                        // account shows up under each of its centers.
                        ->orWhereHas('employee', fn ($e) => $e->where('etablissement_id', $centreId))
                        ->orWhereHas('employee.etablissements', fn ($e) => $e->where('etablissements.id', $centreId));
                });
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%")
                        ->orWhere('username', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $users->through(fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'isActive' => (bool) $user->is_active,
            'mustChangePassword' => (bool) $user->must_change_password,
            'roles' => $user->roles->map(fn ($role) => $role->displayLabel())->values()->all(),
            'employee' => $user->employee === null ? null : [
                'reference' => $user->employee->reference,
                'nomComplet' => $user->employee->nomComplet(),
                'etablissement' => $user->employee->etablissement?->nom_centre,
            ],
        ]);

        return $users;
    }
}
