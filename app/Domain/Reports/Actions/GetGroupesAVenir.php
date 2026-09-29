<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Models\Creneau;
use App\Models\Group;
use App\Models\Inscription;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Carbon;

/**
 * « Groupes à venir » dashboard widget — the groups still « En inscription »
 * (not started yet) in the active année + centre, soonest start first, with
 * how full each one is and its open timetable.
 *
 * Scoped exactly like the « Groupes » stat card of GetDashboardStats (année
 * AND centre from CurrentContext), so the widget's total always equals that
 * card's « En Inscription » badge. Two queries whatever the volume: the
 * groups (+ withCount) and their open créneaux, eager-loaded in one batch.
 */
final class GetGroupesAVenir
{
    public function __construct(private readonly CurrentContext $context) {}

    /**
     * @return array{total: int, groupes: list<array<string, mixed>>}
     */
    public function __invoke(): array
    {
        $anneeId = $this->context->anneeScolaireId();
        $centreId = $this->context->etablissementId();

        $query = Group::query()
            ->where('statut', Group::STATUT_EN_INSCRIPTION)
            ->when($anneeId, fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->when($centreId, fn ($q) => $q->where('etablissement_id', $centreId));

        // Every group is served: the widget is a carousel, not a top-N. The
        // set is small by nature (groups not started yet in one année).
        $groups = $query
            ->with([
                'enseignant:id,nom,prenom',
                'salle:id,nom',
                'etablissement:id,nom_centre',
                'creneaux' => fn ($c) => $c->whereNull('date_fin')->orderBy('jour_semaine')->orderBy('heure_debut'),
            ])
            ->withCount([
                'inscriptions as inscrits_count' => fn ($q) => $q->where('statut', Inscription::STATUT_ACTIVE),
            ])
            // A group with no start date yet is the least imminent: last.
            ->orderByRaw('date_debut_formation IS NULL, date_debut_formation ASC, nom ASC')
            ->get();

        $today = Carbon::today();

        return [
            'total' => $groups->count(),
            'groupes' => $groups->map(fn (Group $group): array => [
                'id' => $group->id,
                'nom' => $group->nom,
                'niveau' => $group->niveau,
                'enseignant' => $group->enseignant?->nomComplet(),
                'salle' => $group->salle?->nom,
                'centre' => $centreId === null ? $group->etablissement?->nom_centre : null,
                'dateDebut' => $group->date_debut_formation?->toDateString(),
                // Signed: negative = the start date has passed while the
                // group is still « En inscription » (it should have started).
                'joursAvantDebut' => $group->date_debut_formation !== null
                    ? (int) $today->diffInDays($group->date_debut_formation->copy()->startOfDay(), false)
                    : null,
                'inscrits' => (int) $group->inscrits_count,
                'capacite' => $group->capacite_max !== null ? (int) $group->capacite_max : null,
                'creneaux' => $group->creneaux->map(fn (Creneau $creneau): array => [
                    'jour' => Creneau::JOURS[$creneau->jour_semaine] ?? '-',
                    'heureDebut' => substr((string) $creneau->heure_debut, 0, 5),
                    'heureFin' => substr((string) $creneau->heure_fin, 0, 5),
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
