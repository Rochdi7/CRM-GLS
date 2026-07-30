<?php

declare(strict_types=1);

namespace App\Domain\Settings\Queries;

use App\Models\AnneeScolaire;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Server-side paginated academic-years list for the Settings → Années
 * scolaires tab — same query/ordering/page-size as the Livewire
 * AnneesScolairesTab::render().
 */
final class GetAnneesScolairesList
{
    public function __invoke(int $perPage = 8): LengthAwarePaginator
    {
        $annees = AnneeScolaire::query()->orderByDesc('date_debut')->paginate($perPage);

        $annees->through(fn (AnneeScolaire $a): array => [
            'id' => $a->id,
            'nom' => $a->nom,
            'dateDebut' => $a->date_debut->format('d/m/Y'),
            'dateFin' => $a->date_fin->format('d/m/Y'),
            'parDefaut' => (bool) $a->par_defaut,
            'inscriptionOuverte' => (bool) $a->inscription_ouverte,
        ]);

        return $annees;
    }
}
