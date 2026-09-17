<?php

declare(strict_types=1);

namespace App\Domain\Payments\Support;

use App\Models\Cheque;
use App\Models\Encaissement;

/**
 * Le chèque suivi qui a RÉELLEMENT financé une ligne d'encaissement — lu à
 * travers la chaîne `applied_from_encaissement_id` (17/09/2026).
 *
 * Une ligne d'application d'avance hérite de l'avance sa caisse, son agent,
 * sa date et sa MÉTHODE — mais pas son `cheque_id`, et c'est voulu : la somme
 * des `encaissements.montant` portant un `cheque_id` est le montant UTILISÉ
 * du chèque (migration create_cheques_table), recopier la clé compterait le
 * même chèque deux fois. Conséquence : une fois RECONVERTIE en avance, une
 * application d'un chèque que la banque a REJETÉ ne « sait » plus d'où vient
 * son argent. Elle passait toutes les gardes « chèque rejeté » — la liste
 * l'offrait à l'application, `AppliquerAvance` l'acceptait, et un
 * remboursement la faisait sortir de la caisse PHYSIQUE alors que ce
 * chèque n'y est jamais entré (le compte Chèque du centre avait été
 * contre-passé). Le trou se voyait dès le deuxième tour de
 * « convertir → appliquer → reconvertir », cf.
 * tests/Feature/Backoffice/Finance/AvanceConvertieEnBoucleTest.php.
 *
 * Cette classe est la SEULE définition de « quel chèque est derrière cet
 * argent » : `AppliquerAvance`, `DeplacerEncaissementVersFrais`, la scission
 * de `ConvertirEncaissementsEnAvance`, `GetEncaissementsList` (`applicable`),
 * `GetInscriptionPayments` (`splittable`), `CaisseResolver::forRemboursement`
 * et l'auditeur `caisse:verifier-coherence` la partagent — jamais un test
 * `$row->cheque_id` recopié ici ou là, sinon l'écran offre ce que l'action
 * refuse, ou l'inverse. Une ligne qui porte son propre `cheque_id` répond
 * immédiatement ; une application remonte jusqu'à la première ligne qui en
 * porte un. UNE requête par niveau de profondeur pour tout un lot (jamais une
 * par ligne — CLAUDE.md §17), bornée comme `ResoudreAllocationsAvance`.
 */
final class ChequeOrigine
{
    private const PROFONDEUR_MAX = 20;

    /**
     * Keyed by the requested encaissement id; null when no tracked cheque is
     * behind that money.
     *
     * @param  list<int>  $encaissementIds
     * @return array<int, ?Cheque>
     */
    public static function pour(array $encaissementIds): array
    {
        $encaissementIds = array_values(array_unique(array_map('intval', $encaissementIds)));

        if ($encaissementIds === []) {
            return [];
        }

        $result = array_fill_keys($encaissementIds, null);
        // Every row visited maps back to the REQUESTED ids that descend from
        // it — several requested rows may share one ancestor.
        $demandeursPar = array_combine($encaissementIds, array_map(fn (int $id): array => [$id], $encaissementIds));
        $frontiere = $encaissementIds;
        $chequeIdPar = [];

        for ($profondeur = 0; $profondeur < self::PROFONDEUR_MAX && $frontiere !== []; $profondeur++) {
            $rows = Encaissement::query()
                ->whereIn('id', $frontiere)
                ->get(['id', 'applied_from_encaissement_id', 'cheque_id']);

            $suivants = [];

            foreach ($rows as $row) {
                $demandeurs = $demandeursPar[(int) $row->id] ?? [];

                if ($row->cheque_id !== null) {
                    foreach ($demandeurs as $demandeur) {
                        $chequeIdPar[$demandeur] = (int) $row->cheque_id;
                    }

                    continue;
                }

                if ($row->applied_from_encaissement_id === null) {
                    continue;
                }

                $parent = (int) $row->applied_from_encaissement_id;
                $suivants[$parent] = [...($suivants[$parent] ?? []), ...$demandeurs];
            }

            $demandeursPar = $suivants;
            $frontiere = array_keys($suivants);
        }

        if ($chequeIdPar !== []) {
            $cheques = Cheque::query()->whereIn('id', array_unique(array_values($chequeIdPar)))->get()->keyBy('id');

            foreach ($chequeIdPar as $demandeur => $chequeId) {
                $result[$demandeur] = $cheques->get($chequeId);
            }
        }

        return $result;
    }

    /** Single-row form for the money ACTIONS (one locked row). */
    public static function de(Encaissement $encaissement): ?Cheque
    {
        if ($encaissement->cheque_id !== null) {
            return $encaissement->cheque;
        }

        if ($encaissement->applied_from_encaissement_id === null) {
            return null;
        }

        return self::pour([(int) $encaissement->id])[(int) $encaissement->id] ?? null;
    }

    /** « Cet argent n'existe pas » : le chèque derrière la ligne a été rejeté par la banque. */
    public static function estRejete(Encaissement $encaissement): bool
    {
        return self::de($encaissement)?->statut === Cheque::STATUT_REJETE;
    }
}
