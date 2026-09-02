<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Finance\Support\CaisseLedger;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Corrige le MONTANT d'un encaissement déjà enregistré — et déplace l'argent
 * en conséquence (02/09/2026).
 *
 * Pourquoi une action et non un simple `update(['montant' => …])` :
 * `montant` n'est pas une étiquette, c'est la somme qui EST tombée dans la
 * caisse. Réécrire la seule colonne laisserait `caisses.solde` sur l'ancien
 * chiffre : le solde et la somme des lignes ne se répondraient plus, et rien
 * dans le journal n'expliquerait l'écart. La correction est donc UNE écriture
 * de caisse du DELTA, dans une transaction, passée par CaisseLedger (jamais
 * increment(), CLAUDE.md §11) :
 *   - montant revu À LA HAUSSE → crédit de l'écart (la caisse avait reçu trop peu) ;
 *   - montant revu À LA BAISSE → débit de l'écart (la caisse avait reçu trop).
 *
 * ⚠ LA CAISSE TOUCHÉE EST CELLE DE LA LIGNE (`caisse_id`), jamais celle de
 * qui corrige. C'est la demande explicite du 02/09/2026 : un super-admin qui
 * rectifie la saisie d'une caissière doit voir l'écart bouger sur LA CAISSE
 * DE CETTE CAISSIÈRE, pas sur la sienne — sinon la correction déplace
 * silencieusement de l'argent d'un till à l'autre et les deux soldes
 * deviennent faux. Même raisonnement que pour un remboursement lié ou une
 * requalification de méthode, qui suivent eux aussi le contexte financier
 * d'ORIGINE et non celui du correcteur (CLAUDE.md §11, dimension centre).
 * `caisse_id` reste donc immuable : cette action ne le lit que pour savoir
 * où écrire.
 *
 * Refusée exactement là où RequalifierMethodeEncaissement l'est, et pour les
 * mêmes raisons : une ligne enchevêtrée avec d'autres écritures ne peut pas
 * voir son montant bougé sans casser celles-ci.
 */
final class CorrigerMontantEncaissement
{
    public function __construct(
        private readonly CaisseLedger $ledger,
    ) {}

    public function handle(Encaissement $encaissement, float $montant): Encaissement
    {
        $montant = round($montant, 2);

        if ($montant <= 0) {
            throw ValidationException::withMessages([
                'montant' => __('The amount must be greater than zero.'),
            ]);
        }

        return DB::transaction(function () use ($encaissement, $montant): Encaissement {
            // Relu sous verrou : une suppression / un remboursement / une
            // application concurrente ne doit pas se glisser entre les
            // garde-fous et le mouvement (CLAUDE.md §11 — tout contrôle
            // « lire un solde puis écrire » se fait dans la transaction).
            $locked = Encaissement::query()
                ->whereKey($encaissement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $ancien = round((float) $locked->montant, 2);

            if (abs($ancien - $montant) < 0.005) {
                // Rien à faire : ni mouvement, ni entrée de journal.
                return $locked;
            }

            $this->guard($locked);
            $this->assertFitsRemainingDue($locked, $montant);

            $caisse = $locked->caisse;

            if ($caisse === null) {
                throw ValidationException::withMessages([
                    'montant' => __('This payment is not attached to a cash account and its amount cannot be corrected.'),
                ]);
            }

            $delta = round($montant - $ancien, 2);

            $motif = "Correction du montant de l'encaissement {$locked->reference} : "
                .number_format($ancien, 2, ',', ' ').' → '
                .number_format($montant, 2, ',', ' ').' DH';

            $extra = [
                'motif_detail' => $motif,
                'montant_avant' => $ancien,
                'montant_apres' => $montant,
                // Dimension centre du journal : celui de l'encaissement,
                // jamais le contexte actif de celui qui corrige.
                'etablissement_id' => $locked->etablissement_id,
            ];

            if ($delta > 0) {
                $this->ledger->credit((int) $caisse->id, $delta, $motif, $locked, $extra);
            } else {
                $this->ledger->debit((int) $caisse->id, abs($delta), $motif, $locked, $extra);
            }

            // `caisse_id` ne bouge pas : on corrige COMBIEN est tombé, pas OÙ.
            // L'édition est journalisée par Auditable.
            $locked->update(['montant' => $montant]);

            return $locked;
        });
    }

    /**
     * Un montant corrigé ne peut pas dépasser ce qui reste dû sur le frais —
     * la même règle que celle appliquée à la CRÉATION
     * (EncaissementController@store), calculée ici hors de la ligne courante
     * pour que réduire puis augmenter un paiement reste possible.
     */
    private function assertFitsRemainingDue(Encaissement $encaissement, float $montant): void
    {
        if ($encaissement->inscription_fee_id === null) {
            // Une avance n'est adossée à aucun frais : rien à dépasser.
            return;
        }

        $fee = InscriptionFee::query()
            ->whereKey($encaissement->inscription_fee_id)
            ->lockForUpdate()
            ->first();

        if ($fee === null) {
            return;
        }

        // Ce que les AUTRES lignes ont déjà payé sur ce frais.
        $payeAilleurs = (float) $fee->encaissements()
            ->whereKeyNot($encaissement->getKey())
            ->sum('montant');

        $reste = round((float) $fee->montant - $payeAilleurs, 2);

        if ($montant > $reste) {
            throw ValidationException::withMessages([
                'montant' => __('The amount exceeds what remains due on this fee (:reste DH).', [
                    'reste' => number_format(max(0.0, $reste), 2, ',', ' '),
                ]),
            ]);
        }
    }

    /**
     * Les cas où bouger le montant casserait une autre écriture — le même
     * périmètre que RequalifierMethodeEncaissement::guard().
     */
    private function guard(Encaissement $encaissement): void
    {
        if ($encaissement->applied_from_encaissement_id !== null) {
            // Une ligne « application » ne fait que réallouer une avance :
            // elle n'a jamais crédité de caisse (AppliquerAvance), son
            // montant est borné par l'avance parente.
            throw ValidationException::withMessages([
                'montant' => __('The amount of an advance allocation cannot be changed — correct it on the advance itself.'),
            ]);
        }

        if ($encaissement->cheque_id !== null) {
            throw ValidationException::withMessages([
                'montant' => __('A payment linked to a tracked cheque keeps its amount — the Cheques module owns that lifecycle.'),
            ]);
        }

        if ($encaissement->remboursements()->exists()) {
            throw ValidationException::withMessages([
                'montant' => __('A refunded payment cannot have its amount corrected: the refund already moved this money out.'),
            ]);
        }

        if ($encaissement->applications()->exists()) {
            // Une avance déjà appliquée à des frais : réduire son montant
            // rendrait les applications supérieures à l'avance elle-même.
            throw ValidationException::withMessages([
                'montant' => __('This advance has already been applied to a fee: detach the allocation before correcting its amount.'),
            ]);
        }
    }
}
