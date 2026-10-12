<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Queries;

use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionLivre;
use App\Models\StockArticle;
use App\Models\StockType;
use Illuminate\Support\Collection;

/**
 * Onglet « Livres » des Étudiants (12/10/2026) — remettre un livre à tout un
 * groupe d'un coup, au lieu de rouvrir le modal de chaque inscription.
 *
 * Chaque ligne porte TOUS les livres déjà remis sur le dossier (pas seulement
 * celui choisi) : l'opérateur doit voir qu'un étudiant a déjà reçu « DaF
 * kompakt A1 » avant de lui remettre « Spektrum A1+ ». `aDejaCeLivre` vient
 * d'ici, jamais redérivé par la page ; l'action le revérifie sous verrou.
 */
final class GetLivresGroupe
{
    /**
     * Livres en stock du centre du groupe — même définition que le
     * multi-select du modal d'inscription (type « Livre », article actif).
     *
     * @return Collection<int, array{id: int, nom: string, quantite: int}>
     */
    public function livresDuCentre(?int $etablissementId): Collection
    {
        return StockArticle::query()
            ->whereHas('stockType', fn ($q) => $q->where('nom', StockType::SYSTEM_LIVRE))
            ->where('statut', StockArticle::STATUT_ACTIF)
            ->where('etablissement_id', $etablissementId)
            ->orderBy('nom')
            ->get(['id', 'nom', 'quantite'])
            ->map(fn (StockArticle $a): array => ['id' => $a->id, 'nom' => $a->nom, 'quantite' => (int) $a->quantite])
            ->values();
    }

    /**
     * Inscriptions du groupe (filtrées par statut si demandé), chacune avec
     * ses livres déjà remis — chargés EN LOT, jamais une requête par ligne.
     *
     * @return list<array<string, mixed>>
     */
    public function lignes(Group $group, ?int $livreId, string $statut = ''): array
    {
        $inscriptions = Inscription::query()
            ->where('group_id', $group->id)
            ->when($statut !== '', fn ($q) => $q->where('statut', $statut))
            ->with('student:id,nom,prenom,reference')
            ->get()
            ->sortBy(fn (Inscription $i): string => ($i->statut === Inscription::STATUT_ACTIVE ? '0' : '1')
                .mb_strtolower((string) $i->student?->nomComplet()))
            ->values();

        $livres = InscriptionLivre::query()
            ->whereIn('inscription_id', $inscriptions->pluck('id'))
            ->with('stockArticle:id,nom')
            ->orderBy('created_at')
            ->get()
            ->groupBy('inscription_id');

        return $inscriptions->map(function (Inscription $i) use ($livres, $livreId): array {
            $remis = $livres->get($i->id) ?? collect();

            return [
                'id' => $i->id,
                'reference' => $i->reference,
                'student' => $i->student?->nomComplet(),
                'studentReference' => $i->student?->reference,
                'studentShowUrl' => $i->student ? route('backoffice.students.show', $i->student) : null,
                'statut' => $i->statut,
                'livres' => $remis->map(fn (InscriptionLivre $l): array => [
                    'id' => (int) $l->stock_article_id,
                    'nom' => $l->stockArticle?->nom,
                    'date' => $l->created_at?->format('d/m/Y'),
                ])->values()->all(),
                'aDejaCeLivre' => $livreId !== null && $remis->contains(fn (InscriptionLivre $l): bool => (int) $l->stock_article_id === $livreId),
            ];
        })->all();
    }
}
