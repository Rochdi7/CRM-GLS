<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Cheque;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Déplace UN encaissement vers le frais d'une AUTRE inscription du MÊME
 * étudiant — l'outil de réparation fiche par fiche, complément de
 * ReaffecterEncaissements (qui travaille par groupe entier).
 *
 * ⚠ Contrairement à AppliquerAvance et au reste de l'application, cette
 * action accepte une inscription DE N'IMPORTE QUEL STATUT (Active,
 * Annulée, Changement, Expirée, Archivée) et ignore le sélecteur de
 * contexte : c'est précisément l'argent mal classé — sur un dossier clos,
 * dans une autre année — qu'il faut pouvoir rapatrier, et ces lignes-là
 * sont justement celles que les écrans normaux masquent. C'est pourquoi
 * elle est réservée au super-admin (`payments.move-fee`).
 *
 * ⚠ CE QUI NE BOUGE JAMAIS : `montant`, `methode`, `date_paiement`,
 * `caisse_id`, `agent_id`, et `caisses.solde`. L'argent est déjà dans la
 * caisse depuis le jour où il a été reçu ; seule son AFFECTATION change.
 * Ré-estampiller la date rejetterait le paiement dans un autre mois du
 * journal de caisse et d'un exercice peut-être déjà rapproché (même
 * raisonnement que le docblock d'AppliquerAvance).
 *
 * La ligne n'est jamais supprimée ni recréée : `inscription_fee_id` est
 * réécrit sur place, et les deux frais (l'ancien et le nouveau) voient
 * leur statut recalculé. Les enregistrements monétaires restent
 * append-only (CLAUDE.md §11).
 */
final class DeplacerEncaissementVersFrais
{
    /**
     * @param  InscriptionFee|null  $cible  null = détacher (l'argent redevient une avance libre)
     */
    public function handle(Encaissement $encaissement, ?InscriptionFee $cible): Encaissement
    {
        return DB::transaction(function () use ($encaissement, $cible): Encaissement {
            // Tout garde-fou qui lit un solde puis écrit doit le faire sous
            // verrou, dans la transaction (CLAUDE.md §11) : sans cela deux
            // déplacements simultanés du même paiement se croisent.
            $row = Encaissement::query()->whereKey($encaissement->getKey())->lockForUpdate()->firstOrFail();

            $ancienFraisId = $row->inscription_fee_id;

            // Un remboursement a déjà fait sortir cet argent de la caisse :
            // le rattacher à un frais ferait apparaître comme payé un frais
            // dont l'argent est parti (même refus que dans
            // ConvertirEncaissementsEnAvance et DetacherEncaissementDuFrais).
            if ($row->remboursements()->exists()) {
                throw ValidationException::withMessages([
                    'encaissement_id' => __('A refunded payment cannot be moved.'),
                ]);
            }

            // Un chèque rejeté n'est pas de l'argent : le compte Chèque a
            // déjà été contre-passé (cf. AppliquerAvance).
            if ($cible !== null && $row->cheque_id !== null && $row->cheque?->statut === Cheque::STATUT_REJETE) {
                throw ValidationException::withMessages([
                    'encaissement_id' => __('This payment was funded by a rejected cheque and cannot be moved.'),
                ]);
            }

            $nouveauFrais = null;

            if ($cible !== null) {
                $nouveauFrais = InscriptionFee::query()->with('inscription')
                    ->whereKey($cible->getKey())->lockForUpdate()->firstOrFail();

                if ($nouveauFrais->getKey() === $ancienFraisId) {
                    throw ValidationException::withMessages([
                        'fee_id' => __('This payment is already attached to that fee.'),
                    ]);
                }

                // ⚠ Le seul garde-fou qu'on ne relâche PAS : l'argent d'un
                // étudiant ne peut jamais solder le frais d'un autre. Le
                // statut de l'inscription est délibérément ignoré ici, mais
                // pas l'identité du payeur — sinon la fiche d'un tiers
                // apparaîtrait payée avec l'argent de quelqu'un d'autre.
                if ($nouveauFrais->inscription === null
                    || $nouveauFrais->inscription->student_id !== $row->student_id) {
                    throw ValidationException::withMessages([
                        'fee_id' => __('This fee does not belong to the student of this payment.'),
                    ]);
                }

                // Le montant déjà réglé sur le frais cible est lu ICI, sous
                // verrou, juste avant l'écriture : c'est le contrôle
                // « lire un solde puis écrire » de §11.
                $dejaPaye = $nouveauFrais->montantPaye();
                $reste = round((float) $nouveauFrais->montant - $dejaPaye, 2);

                if (round((float) $row->montant, 2) > $reste) {
                    throw ValidationException::withMessages([
                        'fee_id' => __('The payment (:montant MAD) exceeds what this fee still owes (:reste MAD).', [
                            'montant' => number_format((float) $row->montant, 2, '.', ' '),
                            'reste' => number_format(max(0.0, $reste), 2, '.', ' '),
                        ]),
                    ]);
                }
            }

            // Seule colonne réécrite. Journalisé par Auditable (LogsActivity
            // suit inscription_fee_id), plus l'entrée explicite ci-dessous.
            $row->update(['inscription_fee_id' => $nouveauFrais?->getKey()]);

            // Les DEUX frais changent d'état : l'ancien redevient dû, le
            // nouveau se rapproche du solde.
            foreach ([$ancienFraisId, $nouveauFrais?->getKey()] as $fraisId) {
                if ($fraisId !== null) {
                    $this->recalculerStatutFee($fraisId);
                }
            }

            $ancien = $ancienFraisId !== null ? InscriptionFee::with('inscription')->find($ancienFraisId) : null;

            activity('encaissement')
                ->performedOn($row)
                ->event('payment_moved')
                ->withProperties([
                    'montant' => number_format((float) $row->montant, 2, '.', ''),
                    'ancien_frais_id' => $ancienFraisId,
                    'ancien_frais' => $ancien?->nom,
                    'ancienne_inscription' => $ancien?->inscription?->reference,
                    'nouveau_frais_id' => $nouveauFrais?->getKey(),
                    'nouveau_frais' => $nouveauFrais?->nom,
                    'nouvelle_inscription' => $nouveauFrais?->inscription?->reference,
                    'etudiant_id' => $row->student_id,
                ])
                ->log($nouveauFrais === null
                    ? "Encaissement {$row->reference} détaché de son frais (redevient une avance)"
                    : "Encaissement {$row->reference} déplacé vers « {$nouveauFrais->nom} » ({$nouveauFrais->inscription?->reference})");

            return $row->refresh();
        });
    }

    private function recalculerStatutFee(int $fraisId): void
    {
        $fee = InscriptionFee::query()->whereKey($fraisId)->first();

        if ($fee === null) {
            return;
        }

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
