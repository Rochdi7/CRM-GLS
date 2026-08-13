<?php

declare(strict_types=1);

namespace App\Domain\Stock\Queries;

use App\Models\StockType;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated + searched stock-types list — same shape as
 * GetTypesDepensesList (its direct model).
 */
final class GetStockTypesList
{
    public function __invoke(string $search, int $perPage): LengthAwarePaginator
    {
        $types = StockType::query()
            ->withCount('articles')
            ->when($search !== '', fn ($q) => $q->where('nom', 'ilike', "%{$search}%"))
            // System types first, then custom ones alphabetically.
            ->orderByDesc('is_system')
            ->orderBy('nom')
            ->paginate($perPage);

        $types->through(fn (StockType $type): array => [
            'id' => $type->id,
            'nom' => $type->nom,
            'statut' => $type->statut,
            'isSystem' => $type->is_system,
            'articlesCount' => $type->articles_count,
        ]);

        return $types;
    }
}
