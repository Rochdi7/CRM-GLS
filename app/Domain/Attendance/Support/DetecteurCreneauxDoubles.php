<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Support;

use App\Models\Creneau;
use Illuminate\Support\Collection;

/**
 * Détecte les créneaux OUVERTS faisant double emploi — même groupe, même jour
 * de la semaine, même heure de début.
 *
 * ⚠ Pourquoi ce n'est pas rattrapable au niveau de la séance.
 * `GenererSeancesDepuisCreneau` est idempotent PAR CRÉNEAU : il ne recrée pas
 * la séance du jour d'un créneau qui en a déjà une. Deux créneaux jumeaux
 * produisent donc chacun légitimement la leur, et le job de 08:00 écrit deux
 * séances identiques sans jamais se répéter lui-même. Le doublon n'est pas un
 * défaut du générateur, c'est un défaut de l'emploi du temps — il se corrige
 * là, à la source (signalé le 07/09/2026 : « Ilyass sept 19H », 5 créneaux
 * saisis le 02/09 puis 5 identiques le 04/09, d'où deux séances par jour et
 * deux appels à faire pour la même classe).
 *
 * Un créneau CLÔTURÉ (`date_fin` renseignée) ne génère plus rien : il n'entre
 * jamais dans un doublon, sinon l'historique d'un changement d'enseignant —
 * l'ancien emploi du temps conservé à côté du nouveau — serait signalé à tort.
 *
 * La comparaison porte sur l'heure de DÉBUT seule, comme le fait le générateur
 * (une même case horaire ne peut pas accueillir deux fois le même groupe,
 * quelle que soit l'heure de fin saisie).
 */
final class DetecteurCreneauxDoubles
{
    /**
     * Les créneaux ouverts du groupe qui en doublonnent un autre — le plus
     * ANCIEN de chaque paire est considéré comme l'original et n'est pas
     * renvoyé : ce sont les copies (ids supérieurs) qui sont en trop.
     *
     * @return Collection<int, Creneau>
     */
    public function doublons(int $groupId): Collection
    {
        return Creneau::query()
            ->where('group_id', $groupId)
            ->whereNull('date_fin')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Creneau $c): string => $c->jour_semaine . '|' . $this->heure($c))
            ->flatMap(fn (Collection $paquet): Collection => $paquet->skip(1))
            ->values();
    }

    /**
     * Les doublons de PLUSIEURS groupes en UNE requête — pour la liste des
     * groupes, qui pagine : une requête par ligne ferait croître le coût avec
     * le nombre de lignes (§ perf, ListPerformanceTest).
     *
     * @param  iterable<int>  $groupIds
     * @return array<int, Collection<int, Creneau>>  indexé par group_id
     */
    public function doublonsParGroupe(iterable $groupIds): array
    {
        $ids = collect($groupIds)->map(fn ($id): int => (int) $id)->unique()->all();

        if ($ids === []) {
            return [];
        }

        return Creneau::query()
            ->whereIn('group_id', $ids)
            ->whereNull('date_fin')
            ->orderBy('id')
            ->get()
            ->groupBy('group_id')
            ->map(fn (Collection $duGroupe): Collection => $duGroupe
                ->groupBy(fn (Creneau $c): string => $c->jour_semaine . '|' . $this->heure($c))
                ->flatMap(fn (Collection $paquet): Collection => $paquet->skip(1))
                ->values())
            ->filter(fn (Collection $c): bool => $c->isNotEmpty())
            ->all();
    }

    /**
     * Existe-t-il déjà un créneau ouvert sur ce jour + cette heure ?
     * `$sauf` exclut le créneau en cours de modification, sinon une simple
     * réédition (changer la salle) se croirait en conflit avec elle-même.
     */
    public function existeDeja(int $groupId, int $jourSemaine, string $heureDebut, ?int $sauf = null): bool
    {
        return Creneau::query()
            ->where('group_id', $groupId)
            ->whereNull('date_fin')
            ->where('jour_semaine', $jourSemaine)
            ->whereRaw('LEFT(heure_debut::text, 5) = ?', [substr($heureDebut, 0, 5)])
            ->when($sauf !== null, fn ($q) => $q->whereKeyNot($sauf))
            ->exists();
    }

    private function heure(Creneau $creneau): string
    {
        return substr((string) $creneau->heure_debut, 0, 5);
    }
}
