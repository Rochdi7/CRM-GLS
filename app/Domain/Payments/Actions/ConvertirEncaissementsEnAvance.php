<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Payments\Support\ChequeOrigine;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Converts fee-attached payments of ONE inscription back into unallocated
 * avances — detaches inscription_fee_id (the row itself is never deleted,
 * money records are append-only, CLAUDE.md §11) and recomputes each touched
 * fee's statut, which drops back to Non payé / Payé partiellement so the fee
 * shows as owed again. caisses.solde is untouched: the money stays in the
 * till, only its allocation changes.
 *
 * This is the "changement de groupe" money-move flow: the old inscription's
 * payments become avances (traceable, with montant utilisé/restant), then
 * AppliquerAvance spends them on the new inscription's fees.
 *
 * An "apply" row (applied_from_encaissement_id set) is converted the same
 * way — its fee is detached but the link to the parent avance is kept, so
 * the parent's used amount stays correct while the detached row's own
 * montant becomes re-allocatable (see Encaissement::isAvance()).
 *
 * ⚠ CONVERSION PARTIELLE — « scinder » un paiement (17/09/2026).
 *
 * Le cas réel : un étudiant paie 1 400 DH de frais sur le groupe A, suit
 * deux semaines, puis change de groupe (autre enseignant). L'école veut
 * garder 700 DH sur le frais du groupe A (les deux semaines enseignées) et
 * libérer 700 DH en avance pour les frais du groupe B. Convertir la ligne
 * ENTIÈRE remettait le frais A à « Non payé » et faisait disparaître les
 * deux semaines payées ; la seule alternative était de saisir la scission
 * à l'oral, hors système.
 *
 * `$montants` (indexé par id d'encaissement) porte le montant À LIBÉRER
 * par ligne ; une ligne absente de la map est convertie en entier — les
 * sept commandes console et les trois actions Domain qui appellent cette
 * classe sans ce paramètre gardent donc exactement leur comportement.
 *
 * La scission N'ÉDITE JAMAIS `montant` ni `caisse_id` (invariants §11) et
 * n'ajoute aucune colonne : elle est COMPOSÉE des deux primitives qui
 * existent déjà, dans UNE transaction, sous verrou :
 *   1. la ligne est détachée de son frais (comme une conversion entière) —
 *      elle redevient une avance de son montant complet ;
 *   2. la part CONSERVÉE (montant − libéré) est aussitôt ré-appliquée au
 *      MÊME frais par `AppliquerAvance`, qui crée la ligne d'application
 *      habituelle (`applied_from` → la ligne détachée ; caisse, agent et
 *      date HÉRITÉS d'elle, jamais du connecté ni d'aujourd'hui).
 * Résultat : le frais A affiche 700 payés (Payé partiellement), et la
 * ligne d'origine figure dans l'onglet Avances avec « utilisé 700 /
 * restant 700 », prête à être appliquée au groupe B. Aucune lecture ne
 * change : une ligne d'application ne crédite jamais une caisse (journal,
 * comptes de caisse, `caisse:verifier-coherence` l'excluent déjà), un
 * remboursement de l'avance est plafonné à son restant (700), et
 * `SupprimerEncaissement` refuse l'avance tant qu'elle porte une
 * application — rien ne peut pendre. `ResoudreAllocationsAvance` suit la
 * chaîne quelle que soit sa profondeur, y compris quand la ligne scindée
 * est elle-même une application d'une avance plus ancienne.
 *
 * Deux refus propres à la scission, absents de la conversion entière :
 * un frais MASQUÉ (l'argent ne se pose jamais sur un frais qui n'est plus
 * dû — même garde qu'`AppliquerAvance`) et un chèque REJETÉ (cet argent n'a
 * jamais existé). `GetInscriptionPayments` porte ces deux règles à l'écran
 * (`splittable`) plutôt que de laisser le modal les redécouvrir.
 *
 * Tests : tests/Feature/Backoffice/Finance/AvanceScindeeSurConversionTest.php
 */
final class ConvertirEncaissementsEnAvance
{
    public function __construct(private readonly AppliquerAvance $appliquer) {}

    /**
     * @param  list<int>  $encaissementIds
     * @param  array<int, float>  $montants  montant to RELEASE per encaissement id — missing key = the whole row
     * @return int number of payments converted
     */
    public function handle(Inscription $inscription, array $encaissementIds, array $montants = []): int
    {
        return DB::transaction(function () use ($inscription, $encaissementIds, $montants): int {
            $encaissements = Encaissement::query()
                ->with(['fee', 'cheque'])
                ->whereIn('id', $encaissementIds)
                ->lockForUpdate()
                ->get();

            foreach ($encaissements as $encaissement) {
                if ($encaissement->fee === null || $encaissement->fee->inscription_id !== $inscription->id) {
                    throw ValidationException::withMessages([
                        'encaissement_ids' => __('One of the selected payments does not belong to this registration.'),
                    ]);
                }

                if ($encaissement->remboursements()->exists()) {
                    throw ValidationException::withMessages([
                        'encaissement_ids' => __('A refunded payment cannot be converted into an advance.'),
                    ]);
                }
            }

            // Every amount is validated against its LOCKED row before a
            // single fee is detached: a bad amount on the third row must not
            // leave the first two converted (§11 « signaler plutôt que
            // masquer » — the operator sees one refusal, not a half-done lot).
            $aConserver = [];

            foreach ($encaissements as $encaissement) {
                $libere = $this->montantLibere($encaissement, $montants);
                $conserve = round((float) $encaissement->montant - $libere, 2);

                if ($conserve > 0) {
                    $this->assertScindable($encaissement);
                    $aConserver[$encaissement->id] = $conserve;
                }
            }

            $feeIds = $encaissements->pluck('inscription_fee_id')->unique()->all();

            foreach ($encaissements as $encaissement) {
                $fee = $encaissement->fee;

                // Audit-logged (LogsActivity tracks inscription_fee_id).
                $encaissement->update(['inscription_fee_id' => null]);

                if (! isset($aConserver[$encaissement->id])) {
                    continue;
                }

                $conserve = $aConserver[$encaissement->id];
                $libere = round((float) $encaissement->montant - $conserve, 2);

                // The kept part goes straight back onto the SAME fee through
                // the one primitive that allocates avance money — so the
                // application row inherits caisse/agent/date from the row it
                // spends, and its own guards (same student, fee still due,
                // amount within both remainders) run under the same lock.
                $application = $this->appliquer->handle($encaissement->fresh(), $fee, $conserve);

                activity('encaissement')
                    ->performedOn($encaissement)
                    ->event('avance_split')
                    ->withProperties([
                        'montant' => number_format((float) $encaissement->montant, 2, '.', ''),
                        'montant_conserve' => number_format($conserve, 2, '.', ''),
                        'montant_libere' => number_format($libere, 2, '.', ''),
                        'frais' => $fee->nom,
                        'frais_id' => $fee->id,
                        'inscription_id' => $inscription->id,
                        'application_reference' => $application->reference,
                        'application_id' => $application->id,
                        'etudiant_id' => $encaissement->student_id,
                        'caisse_id' => $encaissement->caisse_id,
                    ])
                    ->log(sprintf(
                        'Paiement %s scindé : %s DH conservés sur « %s », %s DH libérés en avance',
                        $encaissement->reference,
                        number_format($conserve, 2, ',', ' '),
                        $fee->nom,
                        number_format($libere, 2, ',', ' '),
                    ));
            }

            InscriptionFee::query()->whereIn('id', $feeIds)->get()
                ->each(fn (InscriptionFee $fee) => $this->recalculerStatutFee($fee));

            return $encaissements->count();
        });
    }

    /**
     * The amount this row RELEASES into the avance pool: the whole row when
     * the caller gave none, otherwise the requested amount — which must be
     * positive and never exceed what the row holds. Rounded to the cent so
     * the exact last cents can always be released or kept.
     *
     * @param  array<int, float>  $montants
     */
    private function montantLibere(Encaissement $encaissement, array $montants): float
    {
        $total = round((float) $encaissement->montant, 2);

        if (! array_key_exists($encaissement->id, $montants)) {
            return $total;
        }

        $libere = round((float) $montants[$encaissement->id], 2);

        if ($libere <= 0 || $libere > $total) {
            throw ValidationException::withMessages([
                'montants.'.$encaissement->id => __('The amount to release must be between 0.01 and the payment amount (:montant DH).', [
                    'montant' => number_format($total, 2, '.', ''),
                ]),
            ]);
        }

        return $libere;
    }

    /**
     * A partial conversion re-applies the kept part onto the ORIGINAL fee, so
     * it is refused exactly where AppliquerAvance would refuse that
     * re-application — named here, before anything is detached, instead of
     * surfacing as a puzzling « this fee is no longer active » after the
     * fact. A whole-row conversion of the same payment stays allowed: it
     * puts no money back on the fee.
     */
    private function assertScindable(Encaissement $encaissement): void
    {
        if ($encaissement->fee !== null && $encaissement->fee->estMasque()) {
            throw ValidationException::withMessages([
                'montants.'.$encaissement->id => __('A payment attached to a hidden fee can only be converted in full — nothing can stay on a fee that is no longer due.'),
            ]);
        }

        if (ChequeOrigine::estRejete($encaissement)) {
            throw ValidationException::withMessages([
                'montants.'.$encaissement->id => __('A payment funded by a rejected cheque cannot be split.'),
            ]);
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
