<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Caisse;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use App\Domain\Finance\Support\CaisseLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Exact inverse of EnregistrerEncaissement, in ONE transaction:
 *  1. decrements the till balance it had incremented (caisses.solde is
 *     application-maintained — deleting the row without this silently
 *     corrupts the till),
 *  2. deletes the encaissement row,
 *  3. recomputes the fee's payment statut.
 *
 * Deliberately narrow (CLAUDE.md §11 keeps money records append-only for
 * everyone else): this exists ONLY for the `payments.delete` permission,
 * which is granted by a super-admin. Corrections in normal operation should
 * still use a compensating entry (remboursement), not a delete.
 *
 * Refused when the row is entangled with other records, because unwinding
 * those cannot be done correctly here:
 *  - a payment made with a tracked chèque (Cheques module owns that lifecycle),
 *  - a payment that has already been (partly) refunded.
 *
 * ⚠ AVANCE APPLIQUÉE — détachement en cascade ($detacherApplications).
 *
 * Une avance dont l'argent a été appliqué à des frais ne peut pas être
 * supprimée telle quelle : ses lignes d'application resteraient en base à
 * pointer une ligne parente disparue, le frais afficherait « payé » par un
 * encaissement inexistant, et `caisses.solde` serait débité d'un montant
 * qui n'est sorti nulle part. Le refus n'est donc PAS une permission — un
 * super-admin ne peut pas davantage écrire une caisse fausse (§11).
 *
 * Ce que le super-admin peut faire, c'est DÉFAIRE l'enchevêtrement d'abord.
 * `$detacherApplications` compose la primitive qui existe déjà : chaque
 * ligne d'application est détachée de son frais par
 * `ConvertirEncaissementsEnAvance` (conversion ENTIÈRE — on ne conserve
 * rien sur le frais, puisque l'argent qui le payait est sur le point de
 * disparaître), dans la MÊME transaction et sous le MÊME verrou, avant que
 * l'avance parente ne soit supprimée. Les frais touchés redeviennent dus.
 * Aucun montant n'est édité, aucune colonne ajoutée, et la caisse n'est
 * débitée qu'une seule fois — par l'avance, la seule des deux qui l'avait
 * créditée.
 *
 * La chaîne est suivie sur TOUTE sa profondeur : une application peut avoir
 * été reconvertie puis ré-appliquée ailleurs, et seule la feuille porte
 * encore un frais (cf. ResoudreAllocationsAvance). Un détachement qui ne
 * verrait qu'un niveau laisserait précisément les orphelines qu'il prétend
 * éviter.
 *
 * Les deux autres refus ne cascadent JAMAIS : un chèque suivi et un
 * remboursement ont chacun une contrepartie hors de cette table (le
 * lifecycle du chèque, de l'argent réellement sorti de la caisse), que
 * défaire ici serait une décision monétaire que personne n'a demandée.
 */
final class SupprimerEncaissement
{
    public function __construct(
        private readonly CaisseLedger $ledger,
        private readonly ConvertirEncaissementsEnAvance $convertir,
    ) {}

    /**
     * @param  bool  $detacherApplications  super-admin override: detach the
     *                                      applications of an applied avance
     *                                      (their fees fall back to owed)
     *                                      instead of refusing the delete.
     */
    public function handle(Encaissement $encaissement, bool $detacherApplications = false): void
    {
        DB::transaction(function () use ($encaissement, $detacherApplications): void {
            // Re-read under a row lock so a concurrent apply/convert can't slip
            // in between the guard checks and the delete.
            $locked = Encaissement::query()
                ->whereKey($encaissement->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->guard($locked, $detacherApplications);

            if ($detacherApplications) {
                $this->detacherApplications($locked);

                // The applications no longer hold any of this avance's money,
                // so montantUtilise() has moved — re-read before the delete.
                $locked->refresh();
            }

            $fee = $locked->fee;

            // An "apply" row only reallocates an existing avance — it never
            // incremented solde (see AppliquerAvance), so it must not decrement
            // it either. Only real money-in rows move the till.
            if ($locked->applied_from_encaissement_id === null) {
                $this->ledger->debit(
                    (int) $locked->caisse_id,
                    (float) $locked->montant,
                    "Annulation de l'encaissement {$locked->reference}",
                    $locked,
                    [
                        'motif_detail' => 'Suppression du paiement',
                        // Reversal carries the ORIGINAL payment's centre —
                        // the same context it credits back out of.
                        'etablissement_id' => $locked->etablissement_id,
                    ],
                );
            }

            $locked->delete();

            if ($fee !== null) {
                $this->recalculerStatutFee($fee->fresh());
            }
        });
    }

    private function guard(Encaissement $encaissement, bool $detacherApplications): void
    {
        // Avec l'option, les applications sont défaites juste après (l'argent
        // remboursé reste, lui, un refus ferme — voir plus bas).
        if (! $detacherApplications && $encaissement->isAvance() && $encaissement->montantUtilise() > 0) {
            throw ValidationException::withMessages([
                'encaissement' => __('This advance has already been applied to fees. Remove those allocations first.'),
            ]);
        }

        // A refunded payment already moved money OUT of the till once
        // (EnregistrerRemboursement). Deleting it would debit the same
        // amount again AND orphan the remboursement (FK nullOnDelete), so
        // the trail could no longer explain the outflow.
        if ($encaissement->remboursements()->exists()) {
            throw ValidationException::withMessages([
                'encaissement' => __('A refunded payment cannot be deleted.'),
            ]);
        }

        if ($encaissement->cheque_id !== null) {
            throw ValidationException::withMessages([
                'encaissement' => __('A payment linked to a tracked cheque cannot be deleted.'),
            ]);
        }
    }

    /**
     * Détache de leur frais TOUTES les lignes qui dépensent cet argent, quelle
     * que soit la profondeur de la chaîne : une application peut avoir été
     * reconvertie en avance puis ré-appliquée ailleurs, auquel cas seule la
     * feuille porte encore un frais. Chaque ligne passe par
     * `ConvertirEncaissementsEnAvance` — la primitive que §11 impose pour
     * cette opération — qui recalcule le statut du frais libéré et journalise
     * le détachement ; `caisses.solde` n'est jamais touché, une application
     * n'ayant jamais crédité de caisse.
     *
     * Parcours en largeur avec garde anti-cycle : `applied_from` ne peut pas
     * boucler aujourd'hui, mais une chaîne mal formée ferait tourner la
     * transaction indéfiniment, verrou tenu.
     */
    private function detacherApplications(Encaissement $avance): void
    {
        $vus = [];
        $file = [$avance->getKey()];

        while ($file !== []) {
            $parentIds = array_values(array_diff($file, $vus));
            $vus = array_merge($vus, $parentIds);
            $file = [];

            if ($parentIds === []) {
                continue;
            }

            $applications = Encaissement::query()
                ->whereIn('applied_from_encaissement_id', $parentIds)
                ->lockForUpdate()
                ->get();

            foreach ($applications as $application) {
                // Les enfants d'abord : une fois la ligne détachée de son
                // frais, elle reste la parente de ses propres applications.
                $file[] = $application->getKey();

                if ($application->inscription_fee_id === null) {
                    continue;
                }

                $inscription = $application->fee?->inscription;

                if ($inscription === null) {
                    // Sans inscription joignable, la primitive ne peut pas
                    // faire sa vérification d'appartenance : mieux vaut
                    // refuser tout le geste que détacher à l'aveugle.
                    throw ValidationException::withMessages([
                        'encaissement' => __('An allocation of this advance points at a fee whose registration is missing. Fix that row before deleting.'),
                    ]);
                }

                $this->convertir->handle($inscription, [$application->getKey()]);
            }
        }
    }

    private function recalculerStatutFee(InscriptionFee $fee): void
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
