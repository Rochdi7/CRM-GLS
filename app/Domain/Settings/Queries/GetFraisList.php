<?php

declare(strict_types=1);

namespace App\Domain\Settings\Queries;

use App\Models\Etablissement;
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
        $frais = Frais::query()
            ->withCount('groups')
            // Eager-loaded so the per-center price lines cost one query for
            // the whole page instead of one per row.
            ->with('etablissements:id,nom_centre')
            ->orderBy('nom')
            ->paginate($perPage)
            ->withQueryString();

        $frais->through(fn (Frais $f): array => [
            'id' => $f->id,
            'nom' => $f->nom,
            'montantDefaut' => number_format((float) $f->montant_defaut, 2, '.', ''),
            'statut' => $f->statut,
            'groupsCount' => $f->groups_count,
            'centres' => $f->etablissements
                ->map(fn (Etablissement $e): array => [
                    'etablissementId' => $e->id,
                    'nomCentre' => $e->nom_centre,
                    'montant' => number_format((float) $e->pivot->montant, 2, '.', ''),
                ])
                ->sortBy('nomCentre')
                ->values()
                ->all(),
        ]);

        return $frais;
    }
}
