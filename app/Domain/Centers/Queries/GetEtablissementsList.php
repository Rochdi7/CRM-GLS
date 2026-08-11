<?php

declare(strict_types=1);

namespace App\Domain\Centers\Queries;

use App\Models\Etablissement;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated centers list for the Settings → Établissements tab —
 * same query/ordering/page-size as the Livewire EtablissementsTab::render().
 */
final class GetEtablissementsList
{
    public function __invoke(int $perPage = 8): LengthAwarePaginator
    {
        $etablissements = Etablissement::query()
            ->withCount('salles')
            ->orderByDesc('siege_social')
            ->orderBy('nom_centre')
            ->paginate($perPage);

        $etablissements->through(fn (Etablissement $e): array => [
            'id' => $e->id,
            'nomCentre' => $e->nom_centre,
            'ville' => $e->ville,
            'adresse' => $e->adresse,
            'ice' => $e->ice,
            'telephone' => $e->telephone,
            'email' => $e->email,
            'siegeSocial' => (bool) $e->siege_social,
            'sallesCount' => $e->salles_count,
        ]);

        return $etablissements;
    }
}
