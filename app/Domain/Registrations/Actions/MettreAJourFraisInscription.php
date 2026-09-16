<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Full replacement of an existing inscription's VISIBLE fee lines, in ONE
 * transaction — the edit-modal counterpart to the create form's fee-line
 * table (InscriptionController::store()), now made live for the first time
 * (registrations.manage-fees was previously only checked by a dead
 * controller — see docs/rapports/migration-inertia/phase-9-inscriptions-audit.md §12 point 1).
 *
 * Lines carrying an `id` update that InscriptionFee row (amount/discount/
 * date/note only — statut is always recomputed from actual payments, never
 * trusted from the client); lines with no `id` are created; any VISIBLE
 * existing row absent from the submitted set is deleted (hidden rows —
 * masque_le set — are simply never sent back by the client, since fees()
 * only returns visible ones, and must NOT be swept up by this "omitted =
 * delete" comparison — hiding/restoring a fee is MasquerFraisInscription/
 * RestaurerFraisInscription's job, never a hard delete). Deliberately
 * unrestricted on visible rows — a fee already fully or partially paid can
 * still have its montant changed or be removed (per product decision).
 *
 * ⚠ REMISE SUR UN FRAIS PARTIELLEMENT PAYÉ (16/09/2026) : elle est
 * AUTORISÉE, sans plancher. Si le nouveau montant tombe sous ce qui a déjà
 * été encaissé, le surplus est détaché en avance réapplicable au lieu d'être
 * refusé — même libération que le retrait d'une ligne payée. SEUL un frais
 * ENTIÈREMENT payé refuse la remise : il est soldé, et rendre de l'argent
 * passe par un remboursement (qui sort réellement de la caisse), jamais par
 * une réécriture du prix.
 * Removing a PAID line releases its payments into unallocated avances first
 * (ConvertirEncaissementsEnAvance, the same release RetirerFraisGroupe
 * performs), so the money stays on the student and re-applicable instead of
 * blocking the edit — until 31/08/2026 the delete simply hit the
 * encaissements FK-restrict and the whole edit was refused.
 */
final class MettreAJourFraisInscription
{
    /**
     * Montant total détaché en avance par le dernier handle() — surplus
     * libéré par une remise appliquée sous ce qui avait déjà été payé. Lu par
     * InscriptionController::updateFees() pour l'annoncer à l'écran : de
     * l'argent qui change d'affectation sans que rien ne le dise serait
     * indiscernable d'un montant perdu.
     */
    public float $montantLibere = 0.0;

    public function __construct(
        private readonly ConvertirEncaissementsEnAvance $convertirEnAvance,
    ) {}

    /**
     * @param  list<array{id?: int, frais_id?: ?int, nom: string, montant_initial?: ?float, remise_pct?: ?float, remise_montant?: ?float, date_echeance?: ?string, note?: ?string}>  $lines
     */
    public function handle(Inscription $inscription, array $lines): Inscription
    {
        $this->montantLibere = 0.0;

        try {
            return DB::transaction(function () use ($inscription, $lines): Inscription {
                $keptIds = [];
                $montantLibere = 0.0;

                foreach ($lines as $line) {
                    $initial = (float) ($line['montant_initial'] ?? 0);
                    $remisePct = isset($line['remise_pct']) && $line['remise_pct'] !== null ? (float) $line['remise_pct'] : null;
                    $remiseMontant = isset($line['remise_montant']) && $line['remise_montant'] !== null ? (float) $line['remise_montant'] : null;
                    $montant = InscriptionFee::computeMontant($initial, $remisePct, $remiseMontant);

                    $existing = isset($line['id'])
                        ? InscriptionFee::query()->where('inscription_id', $inscription->id)->findOrFail($line['id'])
                        : null;

                    $attributes = [
                        'inscription_id' => $inscription->id,
                        'frais_id' => $line['frais_id'] ?? null,
                        'nom' => $line['nom'],
                        'montant_initial' => $initial,
                        'remise_pct' => $remisePct,
                        'remise_montant' => $remiseMontant,
                        'montant' => $montant,
                        // date_echeance is NOT NULL — an omitted value keeps
                        // the existing row's date on update, or defaults to
                        // today for a brand-new line (matches store()'s own
                        // `?? now()->toDateString()` fallback).
                        'date_echeance' => $line['date_echeance']
                            ?? $existing?->date_echeance?->toDateString()
                            ?? now()->toDateString(),
                        'note' => $line['note'] ?? null,
                    ];

                    if ($existing !== null) {
                        // Money already received on the line is the floor:
                        // pricing it below the paid amount would make the
                        // surplus vanish instead of becoming an avance (DB-06).
                        // ⚠ Une remise reste possible sur un frais DÉJÀ PAYÉ
                        // EN PARTIE ; seul un frais ENTIÈREMENT payé la refuse
                        // (décision du 16/09/2026). L'ancienne borne « jamais
                        // en dessous du montant déjà payé » rendait la remise
                        // impossible dès le premier dirham encaissé : sur un
                        // frais de 1 300 DH réglé à 600 DH, accorder 50 % (650)
                        // passait, mais 60 % (520) était refusé — l'utilisateur
                        // ne pouvait pas accorder la remise négociée et n'avait
                        // aucun autre chemin, alors que RETIRER la ligne
                        // libérait déjà cet argent sans difficulté.
                        //
                        // Le surplus n'est jamais perdu ni masqué : il est
                        // DÉTACHÉ en avance réapplicable, exactement la même
                        // libération que le retrait d'une ligne payée
                        // (ConvertirEncaissementsEnAvance, §11 « Retirer un
                        // frais DÉJÀ PAYÉ libère toujours son argent en
                        // avance »). caisses.solde ne bouge pas — l'argent
                        // reste en caisse, seule son AFFECTATION change.
                        //
                        // Un frais entièrement payé est en revanche un dossier
                        // SOLDÉ : le remiser après coup revient à rendre de
                        // l'argent, ce qui se fait par un remboursement (qui,
                        // lui, sort réellement de la caisse et porte ses
                        // propres autorisations) — pas en réécrivant le prix.
                        //
                        // ⚠ ET LA RÈGLE NE JUGE QUE LES LIGNES DONT LE PRIX
                        // CHANGE (16/09/2026, second signalement). La table
                        // entière est UN SEUL payload : chaque enregistrement
                        // renvoie les onze lignes, donc contrôler toutes les
                        // lignes faisait échouer l'édition d'un frais à cause
                        // d'une AUTRE ligne que l'utilisateur n'avait pas
                        // touchée. Sur l'inscription 807, « Frais d'inscription
                        // A1/A2/B1 » porte 600 DH sur un frais de 300 (doublon
                        // de l'import legacy — 155 lignes sont dans ce cas en
                        // base) : modifier « Frais de Février » butait sur ce
                        // frais-là, et le message nommait une ligne dont
                        // l'utilisateur n'avait rien fait. Une ligne déjà
                        // sur-payée AVANT cette requête est donc laissée telle
                        // quelle : elle n'est pas le sujet de l'édition, et
                        // c'est un remboursement qui la corrigera.
                        $paye = $existing->montantPaye();
                        $montantActuel = (float) $existing->montant;
                        $prixInchange = abs($montant - $montantActuel) < 0.005;

                        if ($prixInchange) {
                            $existing->update($attributes);
                            $this->recalculerStatut($existing);
                            $keptIds[] = $existing->id;

                            continue;
                        }

                        // SOLDÉ = il ne RESTE plus rien à payer, c'est-à-dire
                        // payé >= prix — l'exact ET le sur-payé. Le critère
                        // est la colonne « RESTE » à 0,00 que l'utilisateur a
                        // sous les yeux, pas l'égalité parfaite : une ligne de
                        // 300 DH ayant reçu 600 DH n'a plus rien à devoir, et
                        // la remiser à 0 libérait 600 DH en avance sur un
                        // frais que l'étudiant avait déjà entièrement réglé
                        // (signalé le 16/09/2026 sur « Frais d'inscription
                        // A1/A2/B1 »). Le sur-payé se corrige par un
                        // remboursement, jamais en réécrivant le prix.
                        $estSolde = $paye + 0.005 >= $montantActuel && $paye > 0.0;

                        if ($montant + 0.005 < $paye && $estSolde) {
                            throw ValidationException::withMessages([
                                'fee_lines' => __('« :fee » is fully paid (:paye DH) — a discount can no longer be applied to it. Refund the student instead.', [
                                    'fee' => $existing->nom,
                                    'paye' => number_format($paye, 2, '.', ''),
                                ]),
                            ]);
                        }

                        $existing->update($attributes);
                        $fee = $existing;

                        // Le surplus part en avance APRÈS l'écriture du
                        // nouveau montant, pour que le statut recalculé par le
                        // convertisseur porte déjà sur le prix remisé.
                        if ($paye > $montant + 0.005) {
                            $libere = $this->libererSurplusEnAvance($inscription, $fee, $montant);

                            if ($libere > 0.0) {
                                $montantLibere += $libere;
                            }
                        }
                    } else {
                        // The same catalog fee already exists on this
                        // registration as a hidden line: adding it again would
                        // duplicate it the day the group restores it (R-07).
                        if (! empty($attributes['frais_id'])
                            && $inscription->fees()->where('frais_id', $attributes['frais_id'])->whereNotNull('masque_le')->exists()) {
                            throw ValidationException::withMessages([
                                'fee_lines' => __('This fee already exists on the registration as a hidden line — restore it instead of adding it again.'),
                            ]);
                        }

                        $fee = $inscription->fees()->create($attributes);
                    }

                    $this->recalculerStatut($fee);
                    $keptIds[] = $fee->id;
                }

                $supprimees = $inscription->fees()
                    ->whereNull('masque_le')
                    ->whereNotIn('id', $keptIds)
                    ->get();

                // ⚠ Money on a removed line becomes an AVANCE, it is never
                // lost and never blocks the edit (31/08/2026). Before this,
                // the delete below hit the encaissements FK-restrict and the
                // whole edit was refused with « ce frais a des paiements » —
                // so a fee added by mistake on a student who had already paid
                // could not be removed at all, and the money had no way back
                // into the avance pool. This is the SAME release the group
                // flow performs (RetirerFraisGroupe): detach the payments,
                // leaving them re-applicable, THEN drop the line. A refunded
                // payment is refused by the converter — its money already
                // left the till — so it is filtered out here rather than
                // aborting the edit.
                if ($supprimees->isNotEmpty()) {
                    $encaissements = Encaissement::query()
                        ->whereIn('inscription_fee_id', $supprimees->pluck('id'))
                        ->whereDoesntHave('remboursements')
                        ->pluck('id')
                        ->all();

                    if ($encaissements !== []) {
                        $this->convertirEnAvance->handle($inscription, $encaissements);
                    }
                }

                $supprimees->each(fn (InscriptionFee $fee) => $fee->delete());

                $inscription->update([
                    'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
                ]);

                $this->montantLibere = round($montantLibere, 2);

                return $inscription->fresh('fees');
            });
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'fee_lines' => __('One of the removed fees has payments and cannot be deleted.'),
            ]);
        }
    }

    /**
     * Détache, du frais qui vient d'être remisé, juste assez d'encaissements
     * pour que ce qui y reste ne dépasse plus le nouveau montant. Les lignes
     * détachées redeviennent des AVANCES réapplicables sur n'importe quel
     * frais de l'étudiant (ConvertirEncaissementsEnAvance) — l'encaissement
     * n'est jamais supprimé et la caisse n'est pas touchée (§11).
     *
     * Trois bornes :
     *  (1) on libère les paiements les PLUS RÉCENTS d'abord, et on s'arrête
     *      dès que le reste tient sous le nouveau montant — le frais conserve
     *      ainsi le maximum de son affectation d'origine ;
     *  (2) un paiement REMBOURSÉ n'est jamais détaché : son argent a déjà
     *      quitté la caisse, le convertisseur le refuse (le retrait de ligne
     *      l'écarte de la même façon) ;
     *  (3) un paiement ne se FRACTIONNE pas — si aucune combinaison n'amène
     *      exactement au montant, on en libère un de plus : le frais se
     *      retrouve sous-payé plutôt que sur-payé, et le solde redevient dû,
     *      ce qui est la situation qu'un écran sait montrer et corriger.
     *
     * @return float le montant réellement libéré
     */
    private function libererSurplusEnAvance(Inscription $inscription, InscriptionFee $fee, float $nouveauMontant): float
    {
        $encaissements = $fee->encaissements()
            ->whereDoesntHave('remboursements')
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->get();

        $restant = (float) $fee->encaissements()->sum('montant');
        $aLiberer = [];
        $total = 0.0;

        foreach ($encaissements as $encaissement) {
            if ($restant <= $nouveauMontant + 0.005) {
                break;
            }

            $aLiberer[] = $encaissement->id;
            $total += (float) $encaissement->montant;
            $restant -= (float) $encaissement->montant;
        }

        if ($aLiberer === []) {
            return 0.0;
        }

        $this->convertirEnAvance->handle($inscription, $aLiberer);

        return $total;
    }

    private function recalculerStatut(InscriptionFee $fee): void
    {
        $paye = $fee->montantPaye();

        $fee->update([
            'statut' => match (true) {
                $paye >= (float) $fee->montant => InscriptionFee::STATUT_PAYE,
                $paye > 0 => InscriptionFee::STATUT_PAYE_PARTIELLEMENT,
                default => InscriptionFee::STATUT_NON_PAYE,
            },
        ]);
    }
}
