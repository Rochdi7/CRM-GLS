<?php

declare(strict_types=1);

namespace App\Domain\Settings\Queries;

use App\Models\Etablissement;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Support\Collection;

/**
 * Center options for a create/edit form's center picker — restricted to
 * centers the acting user may actually act on (Phase 6 §Q3: the Livewire
 * SallesTab and its ResourcePolicy::create() had no such restriction —
 * any rooms.create holder could submit ANY etablissement_id, checked only
 * for existence, not access. Fixed here for the new Inertia code path: the
 * options list itself never contains an inaccessible center, and the
 * controller re-validates the submitted id against this same set.
 */
final class GetAccessibleCenterOptions
{
    public function __construct(private readonly CenterAccessService $centerAccess) {}

    /**
     * @return Collection<int, array{value: int, label: string}>
     */
    public function __invoke(User $user): Collection
    {
        return Etablissement::query()
            ->when(
                ! $this->centerAccess->hasGlobalAccess($user),
                fn ($q) => $q->whereIn('id', $this->centerAccess->accessibleCenterIds($user)),
            )
            ->orderBy('nom_centre')
            ->get()
            ->map(fn (Etablissement $e): array => ['value' => $e->id, 'label' => $e->nom_centre]);
    }

    /**
     * Ids allowed for this user — used to re-validate a submitted etablissement_id server-side.
     *
     * @return list<int>
     */
    public function allowedIds(User $user): array
    {
        if ($this->centerAccess->hasGlobalAccess($user)) {
            return Etablissement::query()->pluck('id')->all();
        }

        return $this->centerAccess->accessibleCenterIds($user);
    }
}
