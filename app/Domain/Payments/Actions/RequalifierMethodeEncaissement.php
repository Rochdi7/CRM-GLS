<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Finance\Support\CaisseLedger;
use App\Domain\Finance\Support\CaisseResolver;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Requalifie la MÉTHODE d'un encaissement déjà enregistré — et déplace
 * l'argent en conséquence (01/09/2026).
 *
 * Pourquoi une action et non un simple `update(['methode' => …])` :
 * `methode` n'est pas une étiquette, c'est ce qui a DÉCIDÉ dans quelle
 * `caisses` l'argent est tombé (CaisseResolver, CLAUDE.md §11) — Espèces
 * dans la caisse physique de l'agent, TPE / Chèque / Virement dans le compte
 * du CENTRE pour cette méthode. Réécrire la seule colonne laisserait
 * l'argent dans un compte et le libellé sur un autre : les deux soldes
 * seraient faux et rien dans le journal n'expliquerait pourquoi.
 *
 * La requalification est donc un TRANSFERT à deux jambes, dans UNE
 * transaction, toutes deux passées par CaisseLedger (jamais increment()) :
 *   1. débit de l'ancienne caisse du montant exact de la ligne,
 *   2. crédit de la caisse résolue pour la nouvelle méthode,
 *   3. mise à jour de `methode` ET de `caisse_id` sur la ligne.
 * `montant` ne bouge pas : une requalification ne crée ni ne détruit
 * d'argent, elle corrige où il est rangé.
 *
 * Le CENTRE de la nouvelle caisse est celui de l'ENCAISSEMENT
 * (`etablissement_id` de la ligne), pas le contexte actif : l'argent a été
 * reçu là-bas, et le corriger depuis un autre centre ne doit pas le
 * déplacer géographiquement — même raisonnement que pour un remboursement
 * lié (CLAUDE.md §11, dimension centre du journal).
 *
 * Refusée quand la ligne est enchevêtrée avec d'autres écritures, pour les
 * mêmes raisons que SupprimerEncaissement :
 *  - une ligne « application » d'avance n'a jamais crédité de caisse, il n'y
 *    a donc rien à déplacer ;
 *  - un chèque suivi (module Chèques) possède son propre cycle de vie et sa
 *    propre caisse ;
 *  - un paiement déjà remboursé a fait sortir l'argent : déplacer la jambe
 *    d'entrée rendrait la sortie inexplicable.
 */
final class RequalifierMethodeEncaissement
{
    public function __construct(
        private readonly CaisseLedger $ledger,
        private readonly CaisseResolver $resolver,
    ) {}

    /**
     * @param  Employee  $agent  l'employé qui effectue la correction — sa
     *                           caisse physique est la destination quand la
     *                           nouvelle méthode est « Espèces »
     */
    public function handle(Encaissement $encaissement, string $methode, Employee $agent): Encaissement
    {
        if (! in_array($methode, Encaissement::METHODES, true)) {
            throw ValidationException::withMessages([
                'methode' => __('Unknown payment method.'),
            ]);
        }

        return DB::transaction(function () use ($encaissement, $methode, $agent): Encaissement {
            // Relu sous verrou : une suppression / un remboursement / une
            // application concurrente ne doit pas se glisser entre les
            // garde-fous et le déplacement (CLAUDE.md §11 — tout contrôle
            // « lire un solde puis écrire » se fait dans la transaction).
            $locked = Encaissement::query()
                ->whereKey($encaissement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->methode === $methode) {
                // Rien à faire : ni mouvement, ni entrée de journal.
                return $locked;
            }

            $this->guard($locked);

            $ancienne = $locked->caisse;

            if ($ancienne === null) {
                throw ValidationException::withMessages([
                    'methode' => __('This payment is not attached to a cash account and cannot be requalified.'),
                ]);
            }

            $nouvelle = $this->resolver->resolveFor($agent, $methode, $locked->etablissement_id);

            if ($nouvelle->id === $ancienne->id) {
                // Même compte des deux côtés (p. ex. l'agent n'a pas changé
                // et les deux méthodes retombent sur la même caisse) : on
                // corrige l'étiquette sans écrire deux mouvements nuls.
                $locked->update(['methode' => $methode]);

                return $locked;
            }

            $montant = (float) $locked->montant;
            $ancienLibelle = $locked->methode;

            $extra = [
                'motif_detail' => "Requalification de méthode : {$ancienLibelle} → {$methode}",
                'methode_avant' => $ancienLibelle,
                'methode_apres' => $methode,
                // Dimension centre du journal : celui de l'encaissement,
                // jamais le contexte actif de celui qui corrige.
                'etablissement_id' => $locked->etablissement_id,
            ];

            $this->ledger->debit(
                (int) $ancienne->id,
                $montant,
                "Requalification de l'encaissement {$locked->reference} ({$ancienLibelle} → {$methode})",
                $locked,
                $extra,
            );

            $this->ledger->credit(
                (int) $nouvelle->id,
                $montant,
                "Requalification de l'encaissement {$locked->reference} ({$ancienLibelle} → {$methode})",
                $locked,
                $extra,
            );

            // `methode` ET `caisse_id` bougent ensemble — c'est tout l'objet
            // de cette action. L'édition est journalisée par Auditable.
            $locked->update([
                'methode' => $methode,
                'caisse_id' => $nouvelle->id,
            ]);

            return $locked;
        });
    }

    /**
     * Les cas où déplacer la jambe d'entrée casserait une autre écriture.
     */
    private function guard(Encaissement $encaissement): void
    {
        if ($encaissement->applied_from_encaissement_id !== null) {
            // Une ligne « application » ne fait que réallouer une avance : elle
            // n'a jamais crédité de caisse (AppliquerAvance), il n'y a donc
            // aucun argent à déplacer — et sa méthode n'est qu'un écho de
            // celle de l'avance parente.
            throw ValidationException::withMessages([
                'methode' => __('The method of an advance allocation cannot be changed — change it on the advance itself.'),
            ]);
        }

        if ($encaissement->cheque_id !== null) {
            throw ValidationException::withMessages([
                'methode' => __('A payment linked to a tracked cheque keeps its method — the Cheques module owns that lifecycle.'),
            ]);
        }

        if ($encaissement->remboursements()->exists()) {
            throw ValidationException::withMessages([
                'methode' => __('A refunded payment cannot be requalified: the refund already moved this money out.'),
            ]);
        }
    }
}
