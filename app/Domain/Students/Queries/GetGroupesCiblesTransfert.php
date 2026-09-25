<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Models\Group;
use Illuminate\Support\Collection;

/**
 * Les groupes qu'un étudiant transféré peut rejoindre dans le centre CIBLE
 * (25/09/2026) : ceux du centre, encore ouverts (ni « Fin de formation »
 * ni « Annulée »), les plus récents d'abord.
 *
 * ⚠ Volontairement HORS de la portée « Centres affectés » du demandeur : le
 * front office de Rabat désigne un groupe de Casablanca qu'il ne peut pas
 * ouvrir par ailleurs — c'est l'objet même de la demande. Seuls le nom, le
 * niveau et l'année sortent ; aucun montant, aucun effectif.
 *
 * @return Collection<int, array{id: int, nom: string, niveau: ?string, statut: string, annee: ?string}>
 */
final class GetGroupesCiblesTransfert
{
    public function __invoke(int $etablissementCibleId): Collection
    {
        return Group::query()
            ->with('anneeScolaire:id,nom,date_debut')
            ->where('etablissement_id', $etablissementCibleId)
            ->whereNotIn('statut', Group::STATUTS_HISTORIQUE)
            ->get(['id', 'nom', 'niveau', 'statut', 'annee_scolaire_id'])
            ->sortBy([
                fn (Group $a, Group $b): int => strcmp(
                    (string) ($b->anneeScolaire?->date_debut?->toDateString() ?? ''),
                    (string) ($a->anneeScolaire?->date_debut?->toDateString() ?? ''),
                ),
                fn (Group $a, Group $b): int => strcasecmp($a->nom, $b->nom),
            ])
            ->values()
            ->map(fn (Group $g): array => [
                'id' => $g->id,
                'nom' => $g->nom,
                'niveau' => $g->niveau,
                'statut' => $g->statut,
                'annee' => $g->anneeScolaire?->nom,
            ]);
    }
}
