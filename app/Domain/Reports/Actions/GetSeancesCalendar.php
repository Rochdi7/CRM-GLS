<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Models\Seance;
use App\Services\Context\CurrentContext;
use Carbon\CarbonImmutable;

/**
 * "Résumé des séances" dashboard calendar — every séance of the requested
 * month, grouped by day, scoped to the active context (center + academic
 * year) exactly like the other dashboard widgets. The React side only lays
 * the days out on a grid; all data selection happens here.
 */
final class GetSeancesCalendar
{
    /**
     * @param  string  $month  'YYYY-MM' (already validated by the controller)
     * @return array{month: string, days: array<string, list<array{id: int, groupNom: ?string,
     *     enseignant: ?string, statut: string, heureDebut: ?string, heureFin: ?string, showUrl: string}>>}
     */
    public function __invoke(CurrentContext $context, string $month): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m', $month)->startOfMonth();
        $end = $start->endOfMonth();

        $centreId = $context->etablissementId();

        $seances = Seance::query()
            ->with(['group:id,nom', 'enseignant:id,nom,prenom'])
            ->whereBetween('date_seance', [$start->toDateString(), $end->toDateString()])
            ->when($centreId, fn ($q) => $q->where(
                fn ($sub) => $sub->whereNull('etablissement_id')->orWhere('etablissement_id', $centreId),
            ))
            ->when($context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->orderBy('date_seance')
            ->orderBy('heure_debut')
            ->orderBy('id')
            ->get();

        $days = [];

        foreach ($seances as $seance) {
            $days[$seance->date_seance->toDateString()][] = [
                'id' => $seance->id,
                'groupNom' => $seance->group?->nom,
                'enseignant' => $seance->enseignant?->nomComplet(),
                'statut' => $seance->statut,
                'heureDebut' => $seance->heure_debut ? substr($seance->heure_debut, 0, 5) : null,
                'heureFin' => $seance->heure_fin ? substr($seance->heure_fin, 0, 5) : null,
                'showUrl' => route('backoffice.seances.show', $seance),
            ];
        }

        return [
            'month' => $start->format('Y-m'),
            'days' => $days,
        ];
    }
}
