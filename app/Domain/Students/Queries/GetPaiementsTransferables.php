<?php

declare(strict_types=1);

namespace App\Domain\Students\Queries;

use App\Domain\Students\Support\SelectionPaiementsTransfert;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * L'arbre « année → inscription → paiements » que le modal de demande de
 * transfert coche, et que le dialogue de validation relit (14/10/2026).
 *
 * Chaque ligne porte son sort tel que `SelectionPaiementsTransfert` le
 * décide (`blocage`, `suitAvanceId`) — la page ne redérive rien, elle
 * dessine. Les avances n'ont ni frais, ni dossier, ni année : elles forment
 * un groupe à part en tête. `defautIds` est la sélection pré-cochée (celle
 * que le transfert emportait avant la case à cocher).
 *
 * `$seulement` restreint l'arbre aux lignes d'une demande déjà déposée
 * (plus les applications qui suivent une avance cochée) — c'est ce que le
 * super-admin voit avant de valider.
 */
final class GetPaiementsTransferables
{
    /**
     * @param  ?list<int>  $seulement
     * @return array{
     *   avances: list<array<string, mixed>>,
     *   annees: list<array{annee: ?string, inscriptions: list<array<string, mixed>>}>,
     *   defautIds: list<int>,
     *   total: string
     * }
     */
    public function __invoke(Student $student, ?array $seulement = null): array
    {
        $lignes = SelectionPaiementsTransfert::lignes($student);
        $defaut = SelectionPaiementsTransfert::defautIds($lignes);

        if ($seulement !== null) {
            $choisis = array_fill_keys(array_map('intval', $seulement), true);
            $lignes = $lignes->filter(fn (array $l): bool => isset($choisis[(int) $l['row']->id])
                || ($l['parentId'] !== null && isset($choisis[$l['parentId']])))->values();
        }

        $format = fn (array $l): array => [
            'id' => (int) $l['row']->id,
            'reference' => $l['row']->reference,
            'date' => $l['row']->date_paiement?->format('d/m/Y'),
            'montant' => number_format((float) $l['row']->montant, 2, '.', ''),
            'rembourse' => number_format($l['rembourse'], 2, '.', ''),
            'methode' => $l['row']->methode,
            'caisse' => $l['row']->caisse?->nom,
            'frais' => $l['row']->fee?->nom,
            'feeId' => $l['row']->inscription_fee_id === null ? null : (int) $l['row']->inscription_fee_id,
            'type' => $l['type'],
            'blocage' => $l['blocage'],
            'blocageLabel' => SelectionPaiementsTransfert::blocageLabel($l['blocage']),
            'suitAvanceId' => $l['parentId'],
        ];

        [$avances, $surFrais] = $lignes->partition(fn (array $l): bool => $l['row']->inscription_fee_id === null);

        $annees = $surFrais
            ->groupBy(fn (array $l): string => (string) ($l['row']->fee?->inscription?->annee_scolaire_id ?? 0))
            ->map(function (Collection $groupeAnnee) use ($format): array {
                /** @var Encaissement $premiere */
                $premiere = $groupeAnnee->first()['row'];
                $annee = $premiere->fee?->inscription?->anneeScolaire;

                $inscriptions = $groupeAnnee
                    ->groupBy(fn (array $l): int => (int) $l['row']->fee?->inscription_id)
                    ->map(function (Collection $groupeIns) use ($format): array {
                        /** @var Inscription|null $inscription */
                        $inscription = $groupeIns->first()['row']->fee?->inscription;

                        return [
                            'id' => $inscription?->id,
                            'reference' => $inscription?->reference,
                            'statut' => $inscription?->statut,
                            'active' => $inscription?->statut === Inscription::STATUT_ACTIVE,
                            'groupe' => $inscription?->group?->nom,
                            'niveau' => $inscription?->group?->niveau,
                            'dateInscription' => $inscription?->date_inscription?->format('d/m/Y'),
                            'paiements' => $groupeIns->map($format)->values()->all(),
                        ];
                    })
                    ->sortBy([
                        fn (array $a, array $b): int => (int) $b['active'] <=> (int) $a['active'],
                        fn (array $a, array $b): int => strcmp((string) $b['reference'], (string) $a['reference']),
                    ])
                    ->values()
                    ->all();

                return [
                    'annee' => $annee?->nom,
                    'dateDebut' => $annee?->date_debut?->toDateString(),
                    'inscriptions' => $inscriptions,
                ];
            })
            ->sortByDesc('dateDebut')
            ->values()
            ->map(fn (array $a): array => ['annee' => $a['annee'], 'inscriptions' => $a['inscriptions']])
            ->all();

        return [
            'avances' => $avances->map($format)->values()->all(),
            'annees' => $annees,
            'defautIds' => $defaut,
            'total' => number_format((float) $lignes
                ->filter(fn (array $l): bool => $l['type'] !== SelectionPaiementsTransfert::TYPE_APPLICATION)
                ->sum(fn (array $l): float => (float) $l['row']->montant), 2, '.', ''),
        ];
    }
}
