<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Queries;

use App\Models\TypeDepense;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated + searched expense-types list — same query/
 * ordering/page-size as the Livewire TypesDepensesIndex::render().
 */
final class GetTypesDepensesList
{
    public function __invoke(string $search, int $perPage): LengthAwarePaginator
    {
        $types = TypeDepense::query()
            ->withCount('depenses')
            ->when($search !== '', fn ($q) => $q->where('nom', 'ilike', "%{$search}%"))
            // System types first, then custom ones alphabetically.
            ->orderByDesc('is_system')
            ->orderBy('nom')
            ->paginate($perPage);

        $types->through(fn (TypeDepense $type): array => [
            'id' => $type->id,
            'nom' => $type->nom,
            'statut' => $type->statut,
            'isSystem' => $type->is_system,
            'depensesCount' => $type->depenses_count,
        ]);

        return $types;
    }
}
