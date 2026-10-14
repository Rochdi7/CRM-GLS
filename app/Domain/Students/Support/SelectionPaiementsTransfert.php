<?php

declare(strict_types=1);

namespace App\Domain\Students\Support;

use App\Domain\Payments\Support\ChequeOrigine;
use App\Models\Cheque;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * QUELS paiements partent avec un étudiant transféré (14/10/2026).
 *
 * Avant, « l'étudiant emporte TOUT son paiement » : chaque avance et chaque
 * frais payé d'un dossier Actif suivaient la copie, sans que le demandeur
 * puisse rien en dire. Désormais la demande COCHE les paiements à déplacer
 * (`student_transfers.encaissement_ids`), et cette classe est la SEULE
 * définition de « ce paiement peut-il partir, et comment ? » — partagée par
 * le modal de demande (`GetPaiementsTransferables`), par la demande
 * (`DemanderTransfertEtudiant`, qui refuse un id hors règle) et par la
 * validation (`ValiderTransfertEtudiant`, qui rejoue le plan SOUS VERROU :
 * un remboursement saisi entre la demande et la décision change la réponse).
 * Jamais recopiée dans un read-model, sinon l'écran coche ce que le serveur
 * refuse (CLAUDE.md §5).
 *
 * Trois sortes de lignes, deux unités de choix :
 *  - une AVANCE (aucun frais) se coche SEULE : elle part si elle est
 *    cochée — `student_id` réécrit, rien d'autre ;
 *  - un PAIEMENT posé sur un frais se coche AVEC SON FRAIS : « un paiement
 *    reste un paiement » (décision du propriétaire, 14/10/2026 — la
 *    première version convertissait une sélection partielle en avance à
 *    ré-appliquer au nouveau centre ; RETIRÉE, ne pas la réintroduire). La
 *    ligne de frais est DÉPLACÉE telle quelle sur le nouveau dossier, ses
 *    paiements la suivent (même mécanisme que « Changement de groupe »),
 *    quel que soit le statut du dossier d'origine ; une sélection qui ne
 *    couvre pas toutes les lignes mobiles d'un frais est REFUSÉE en nommant
 *    le frais — jamais exécutée à moitié ;
 *  - une ligne d'APPLICATION d'avance n'est jamais cochée seule : son argent
 *    est celui de l'avance PARENTE, elle SUIT cette avance (cochée ⇒ son
 *    frais part entier avec elle).
 *
 * Ne partent JAMAIS, et l'écran le DIT au lieu de masquer la ligne : un
 * paiement entièrement remboursé (plus rien à emporter — il suit son frais
 * si celui-ci part, sans compter) et une ligne financée par un chèque
 * REJETÉ (cet argent n'a jamais existé — `ChequeOrigine`, la chaîne
 * `applied_from` comprise).
 *
 * ⚠ Aucun montant ne bouge, `caisses.solde` n'est ni lu ni écrit : seule
 * l'AFFECTATION change (CLAUDE.md §11, bloc « transfert d'étudiant »).
 *
 * Tests : tests/Feature/Backoffice/Students/TransfertEtudiantSelectionPaiementsTest.php
 */
final class SelectionPaiementsTransfert
{
    public const TYPE_AVANCE = 'avance';

    public const TYPE_PAIEMENT = 'paiement';

    public const TYPE_APPLICATION = 'application';

    public const BLOCAGE_APPLICATION = 'application';

    public const BLOCAGE_REMBOURSE = 'rembourse';

    public const BLOCAGE_CHEQUE_REJETE = 'cheque_rejete';

    /**
     * Toutes les lignes d'encaissement de l'étudiant, qualifiées. Une
     * requête pour les lignes, une pour le remboursé (withSum), une par
     * niveau de chaîne pour le chèque d'origine — jamais une par ligne (§17).
     *
     * @return Collection<int, array{row: Encaissement, type: string, blocage: ?string, rembourse: float, parentId: ?int}>
     */
    public static function lignes(Student $student, bool $lock = false): Collection
    {
        if ($lock) {
            // Verrou posé à part : FOR UPDATE ne se combine pas avec le
            // withSum de la lecture ci-dessous.
            Encaissement::query()->where('student_id', $student->id)->lockForUpdate()->pluck('id');
        }

        $rows = Encaissement::query()
            ->with([
                'fee:id,inscription_id,nom,masque_le',
                'fee.inscription:id,reference,statut,group_id,annee_scolaire_id,date_inscription',
                'fee.inscription.group:id,nom,niveau',
                'fee.inscription.anneeScolaire:id,nom,date_debut',
                'caisse:id,nom',
            ])
            ->withSum(['remboursements as remboursements_total' => fn ($q) => $q->nonAnnules()], 'montant')
            ->where('student_id', $student->id)
            ->orderBy('date_paiement')
            ->orderBy('id')
            ->get();

        $chequesRejetes = collect(ChequeOrigine::pour($rows->pluck('id')->all()))
            ->filter(fn ($cheque): bool => $cheque !== null && $cheque->statut === Cheque::STATUT_REJETE)
            ->keys()
            ->all();

        return $rows->map(function (Encaissement $row) use ($chequesRejetes): array {
            $rembourse = round((float) ($row->remboursements_total ?? 0), 2);
            $type = $row->applied_from_encaissement_id !== null
                ? self::TYPE_APPLICATION
                : ($row->inscription_fee_id === null ? self::TYPE_AVANCE : self::TYPE_PAIEMENT);

            $blocage = match (true) {
                $type === self::TYPE_APPLICATION => self::BLOCAGE_APPLICATION,
                in_array($row->id, $chequesRejetes, true) => self::BLOCAGE_CHEQUE_REJETE,
                $rembourse >= round((float) $row->montant, 2) => self::BLOCAGE_REMBOURSE,
                default => null,
            };

            return [
                'row' => $row,
                'type' => $type,
                'blocage' => $blocage,
                'rembourse' => $rembourse,
                'parentId' => $row->applied_from_encaissement_id === null ? null : (int) $row->applied_from_encaissement_id,
            ];
        })->values();
    }

    /**
     * La sélection PAR DÉFAUT — ce que le transfert emportait avant la case
     * à cocher : chaque avance et chaque paiement d'un dossier encore Actif.
     * Les paiements d'un dossier clos restent l'histoire du centre source
     * tant qu'on ne les coche pas. Une demande antérieure à la colonne
     * (`encaissement_ids` NULL) est validée avec cette sélection.
     *
     * @param  Collection<int, array{row: Encaissement, type: string, blocage: ?string, rembourse: float, parentId: ?int}>  $lignes
     * @return list<int>
     */
    public static function defautIds(Collection $lignes): array
    {
        return $lignes
            ->filter(fn (array $l): bool => $l['blocage'] === null
                && ($l['type'] === self::TYPE_AVANCE
                    || $l['row']->fee?->inscription?->statut === Inscription::STATUT_ACTIVE))
            ->map(fn (array $l): int => (int) $l['row']->id)
            ->values()
            ->all();
    }

    /**
     * Le PLAN d'exécution d'une sélection — refuse en nommant la ligne dès
     * qu'un id n'est pas un paiement cochable de cet étudiant, pour que la
     * demande comme la validation échouent entières plutôt qu'à moitié
     * (§11 « signaler plutôt que masquer »).
     *
     * @param  ?list<int>  $ids  null = sélection par défaut
     * @return array{
     *   ids: list<int>,
     *   avances: list<int>,
     *   feesEntiers: list<int>,
     *   montant: float,
     *   lignes: Collection<int, array{row: Encaissement, type: string, blocage: ?string, rembourse: float, parentId: ?int}>
     * }
     */
    public static function plan(Student $student, ?array $ids, bool $lock = false, string $field = 'encaissement_ids'): array
    {
        $lignes = self::lignes($student, $lock);
        $parId = $lignes->keyBy(fn (array $l): int => (int) $l['row']->id);

        $ids = $ids === null
            ? self::defautIds($lignes)
            : array_values(array_unique(array_map('intval', $ids)));

        foreach ($ids as $id) {
            $ligne = $parId->get($id);

            if ($ligne === null) {
                throw ValidationException::withMessages([
                    $field => __('One of the selected payments does not belong to this student.'),
                ]);
            }

            if ($ligne['blocage'] !== null) {
                throw ValidationException::withMessages([
                    $field => __('Payment :reference cannot be transferred: :reason', [
                        'reference' => $ligne['row']->reference,
                        'reason' => self::blocageLabel($ligne['blocage']),
                    ]),
                ]);
            }
        }

        $choisis = array_fill_keys($ids, true);

        // Les lignes qui PARTENT : les cochées + les applications dont
        // l'avance parente est cochée.
        $partent = $lignes->filter(fn (array $l): bool => isset($choisis[(int) $l['row']->id])
            || ($l['parentId'] !== null && isset($choisis[$l['parentId']])));

        $avances = $partent
            ->filter(fn (array $l): bool => $l['type'] === self::TYPE_AVANCE)
            ->map(fn (array $l): int => (int) $l['row']->id)
            ->values()
            ->all();

        $feesEntiers = [];

        $surFrais = $partent->filter(fn (array $l): bool => $l['row']->inscription_fee_id !== null);

        foreach ($surFrais->groupBy(fn (array $l): int => (int) $l['row']->inscription_fee_id) as $feeId => $groupe) {
            // Tout ce qui est MOBILE sur ce frais : les paiements cochables
            // et les applications (qui suivent leur avance). Un frais part
            // ENTIER ou pas du tout — un paiement reste un paiement.
            $mobiles = $lignes->filter(fn (array $l): bool => (int) $l['row']->inscription_fee_id === $feeId
                && ($l['blocage'] === null || $l['type'] === self::TYPE_APPLICATION));

            if ($mobiles->count() !== $groupe->count()) {
                throw ValidationException::withMessages([
                    $field => __('The payments of fee ":frais" move together: check all of them or none.', [
                        'frais' => $groupe->first()['row']->fee?->nom ?? '#'.$feeId,
                    ]),
                ]);
            }

            $feesEntiers[] = $feeId;
        }

        $montant = round((float) $partent
            ->filter(fn (array $l): bool => $l['type'] !== self::TYPE_APPLICATION)
            ->sum(fn (array $l): float => (float) $l['row']->montant), 2);

        return [
            'ids' => $ids,
            'avances' => $avances,
            'feesEntiers' => array_values(array_unique($feesEntiers)),
            'montant' => $montant,
            'lignes' => $lignes,
        ];
    }

    public static function blocageLabel(?string $blocage): ?string
    {
        return match ($blocage) {
            self::BLOCAGE_APPLICATION => __('follows its advance'),
            self::BLOCAGE_REMBOURSE => __('fully refunded'),
            self::BLOCAGE_CHEQUE_REJETE => __('funded by a rejected cheque'),
            default => null,
        };
    }
}
