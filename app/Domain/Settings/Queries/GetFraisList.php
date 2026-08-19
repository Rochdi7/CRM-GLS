<?php

declare(strict_types=1);

namespace App\Domain\Settings\Queries;

use App\Models\Frais;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated fee-catalog list for the Settings → Frais tab —
 * same query/ordering/page-size as the Livewire FraisTab::render().
 */
final class GetFraisList
{
    public function __invoke(int $perPage = 8): LengthAwarePaginator
    {
        $frais = Frais::query()->withCount('groups')->orderBy('nom')->paginate($perPage)->withQueryString();

        $frais->through(fn (Frais $f): array => [
            'id' => $f->id,
            'nom' => $f->nom,
            'montantDefaut' => number_format((float) $f->montant_defaut, 2, '.', ''),
            'statut' => $f->statut,
            'groupsCount' => $f->groups_count,
        ]);

        return $frais;
    }
}
