<?php

declare(strict_types=1);

namespace App\Domain\Groups\Actions;

use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\MotifAnnulation;
use Illuminate\Support\Facades\DB;

/**
 * Clôture en cascade des inscriptions d'un groupe qui passe dans un statut
 * TERMINAL — « Fin de formation » ou « Annulée » (09/09/2026).
 *
 * Un groupe clos ne peut plus rien enseigner : laisser ses inscriptions
 * « Active » faisait réclamer par « Gestion des recouvrements » des frais
 * que plus personne ne doit (même famille de problème que le filtre
 * `Active` de GetRetardsList, CLAUDE.md §11), et gardait le dossier de
 * l'étudiant ouvert sur une formation terminée.
 *
 * Trois effets, dans UNE transaction, et rien d'autre :
 *
 *  1. chaque inscription encore `Active` du groupe passe `Annulée`, avec le
 *     motif système « Clôture du groupe », `date_fin` = date de fin du
 *     groupe (ou aujourd'hui), et une note ajoutée — jamais écrasée
 *     (AnnulerInscription::appendNote, même règle) ;
 *  2. ses lignes de frais N'AYANT REÇU AUCUN DIRHAM sont MASQUÉES
 *     (`masque_le`), jamais supprimées, et `montant_total` est recalculé ;
 *  3. un frais du catalogue du groupe (`group_frais`) n'est détaché que si
 *     AUCUN étudiant du groupe ne l'a payé — voir detacherFraisNonPayes().
 *
 * ⚠ Aucun argent ne bouge. C'est l'invariant central :
 *
 *  - une ligne qui a reçu le moindre paiement — « Payé » comme « Payé
 *    partiellement » — n'est JAMAIS masquée. Le critère est l'ABSENCE
 *    d'encaissement (`whereDoesntHave('encaissements')`), pas le statut :
 *    un statut peut avoir dérivé, un encaissement non. Un reste dû sur une
 *    prestation commencée s'annule par un remboursement, pas en effaçant la
 *    créance ;
 *  - par construction, aucun frais masqué ici ne porte d'argent, donc la
 *    règle §11 « retirer un frais payé libère son argent en avance » n'est
 *    pas contournée : elle est sans objet. `caisses.solde` n'est ni lu ni
 *    écrit, aucun encaissement n'est détaché ni supprimé.
 *
 * Les inscriptions déjà `Annulée` / `Changement` / `Expirée` / `Archivée`
 * ne sont pas retouchées : leur dossier est clos et son motif d'origine
 * doit le rester.
 *
 * Rouvrir le groupe (GroupController@rouvrir) ne défait RIEN de tout ceci —
 * comme la réouverture elle-même ne touche que `statut`. Les inscriptions
 * se réactivent une par une, et un frais masqué se restaure depuis la
 * corbeille du modal : ré-activer en masse serait une décision monétaire
 * prise en silence.
 */
final class CloturerInscriptionsGroupe
{
    /** Motif système écrit sur les inscriptions annulées par cette cascade. */
    public const MOTIF = MotifAnnulation::MOTIF_CLOTURE_GROUPE;

    /**
     * @return array{inscriptionsAnnulees: int, feesMasques: int, fraisDetaches: int}
     *         chiffres remontés à l'utilisateur : une clôture qui retire des
     *         créances ne doit jamais être silencieuse.
     */
    public function handle(Group $group, ?string $note = null): array
    {
        return DB::transaction(function () use ($group, $note): array {
            $this->assurerMotif();

            $dateFin = ($group->date_fin_formation ?? now())->format('Y-m-d');

            $inscriptions = $group->inscriptions()
                ->where('statut', Inscription::STATUT_ACTIVE)
                ->lockForUpdate()
                ->get();

            $feesMasques = 0;

            foreach ($inscriptions as $inscription) {
                $feesMasques += $this->masquerFraisNonPayes($inscription);

                $inscription->update([
                    'statut' => Inscription::STATUT_ANNULEE,
                    'motif_annulation' => self::MOTIF,
                    'date_fin' => $inscription->date_fin?->format('Y-m-d') ?? $dateFin,
                    'note' => $this->appendNote($inscription->note, $note),
                    // Les lignes masquées ne sont plus dues : le total stocké
                    // doit suivre, même expression que AnnulerInscription.
                    'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
                ]);
            }

            return [
                'inscriptionsAnnulees' => $inscriptions->count(),
                'feesMasques' => $feesMasques,
                'fraisDetaches' => $this->detacherFraisNonPayes($group),
            ];
        });
    }

    /**
     * Masque les lignes de frais VISIBLES n'ayant reçu aucun encaissement.
     * `MASQUE_ORIGINE_GROUPE` : c'est le groupe qui les a retirées, donc la
     * restauration par ligne et RetirerFraisGroupe::restore() les
     * reconnaissent comme telles.
     */
    private function masquerFraisNonPayes(Inscription $inscription): int
    {
        $fees = $inscription->fees()
            ->whereNull('masque_le')
            ->whereDoesntHave('encaissements')
            ->lockForUpdate()
            ->get();

        $fees->each(fn (InscriptionFee $fee) => $fee->update([
            'masque_le' => now(),
            'masque_origine' => InscriptionFee::MASQUE_ORIGINE_GROUPE,
        ]));

        return $fees->count();
    }

    /**
     * Retire du catalogue du groupe (`group_frais`) les frais que PERSONNE
     * n'a payés dans ce groupe.
     *
     * Le test porte sur TOUS les étudiants du groupe, quel que soit le
     * statut de leur inscription : un seul encaissement, même sur une
     * inscription déjà annulée l'an dernier, suffit à garder le frais
     * attaché. Détacher un frais que quelqu'un a payé rendrait son montant
     * de référence introuvable au moment de relire cet argent (le pivot
     * porte `montant` / `date_echeance` / `classification`), pour ne gagner
     * qu'une ligne de catalogue.
     */
    private function detacherFraisNonPayes(Group $group): int
    {
        $fraisIds = $group->frais()->pluck('frais.id');

        if ($fraisIds->isEmpty()) {
            return 0;
        }

        // Les frais du groupe qui portent au moins un dirham, sur n'importe
        // quelle inscription du groupe — une seule requête, jamais une par
        // frais (CLAUDE.md § performance).
        $fraisPayes = Encaissement::query()
            ->join('inscription_fees', 'encaissements.inscription_fee_id', '=', 'inscription_fees.id')
            ->join('inscriptions', 'inscription_fees.inscription_id', '=', 'inscriptions.id')
            ->where('inscriptions.group_id', $group->id)
            ->whereIn('inscription_fees.frais_id', $fraisIds)
            ->distinct()
            ->pluck('inscription_fees.frais_id');

        $aDetacher = $fraisIds->diff($fraisPayes);

        if ($aDetacher->isEmpty()) {
            return 0;
        }

        $group->frais()->detach($aDetacher->all());

        return $aDetacher->count();
    }

    /**
     * Le motif est catalogué (jamais du texte libre, CLAUDE.md §11) et
     * `is_system` : il est posé par le code, donc il doit exister et rester
     * actif — même contrat que « Changement de groupe ».
     */
    private function assurerMotif(): void
    {
        MotifAnnulation::query()->updateOrCreate(
            ['nom' => self::MOTIF],
            [
                'statut' => MotifAnnulation::STATUT_ACTIF,
                'is_system' => true,
                'portee' => MotifAnnulation::PORTEE_INSCRIPTION,
            ],
        );
    }

    private function appendNote(?string $existing, ?string $added): ?string
    {
        $added = $added !== null ? trim($added) : '';

        if ($added === '') {
            return $existing;
        }

        return trim(($existing ?? '') === '' ? $added : $existing."\n".$added);
    }
}
