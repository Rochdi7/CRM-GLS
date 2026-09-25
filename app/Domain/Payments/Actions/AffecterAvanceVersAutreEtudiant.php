<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Encaissement;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Affecte une AVANCE au frais d'un AUTRE étudiant (21/09/2026, outil de
 * maintenance « Déplacer un paiement »).
 *
 * LE CAS RÉEL : 300 + 200 DH encaissés en avance au nom de MALAK AGDALI,
 * qui n'a aucune inscription — l'argent avait été remis pour sa sœur AYA,
 * dont le frais de Septembre restait dû de 500 DH. Aucune action
 * d'exploitation ne couvre ce geste, et c'est voulu :
 *   - `AppliquerAvance` exige le MÊME étudiant (l'argent d'un étudiant ne
 *     solde jamais le frais d'un autre) ;
 *   - `TransfererFraisVersAutreEtudiant` refuse une avance (« rien à
 *     céder ») : elle transfère un FRAIS payé, et une avance n'en a pas.
 * Ni l'une ni l'autre ne doit s'assouplir « pour faire pareil » : c'est
 * CETTE action, réservée au compte de maintenance, qui porte l'exception
 * avec ses propres bornes.
 *
 * ⚠ AUCUN ARGENT NE BOUGE : `montant`, `methode`, `date_paiement`,
 * `caisse_id`, `agent_id` et `caisses.solde` sont inchangés — la caisse a
 * reçu ces dirhams le jour où ils ont été remis, seule leur AFFECTATION
 * change. `student_id` suit le frais, comme dans le transfert de frais :
 * sans cela la fiche de MALAK continuerait de compter cet argent.
 *
 * Le frais cible est DÉSIGNÉ par l'appelant (une avance n'a pas de nom de
 * frais à faire correspondre), mais vérifié ici sous verrou : non masqué,
 * d'un autre étudiant, du même centre, et jamais au-delà de son reste dû —
 * les mêmes gardes qu'`AppliquerAvance` moins « même étudiant ».
 */
final class AffecterAvanceVersAutreEtudiant
{
    public function handle(Encaissement $avance, InscriptionFee $fraisCible, string $motif): Encaissement
    {
        return DB::transaction(function () use ($avance, $fraisCible, $motif): Encaissement {
            $row = Encaissement::query()
                ->with('student')
                ->whereKey($avance->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $fee = InscriptionFee::query()
                ->with('inscription.student')
                ->whereKey($fraisCible->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $cible = $fee->inscription;

            if (! $row->isAvance()) {
                throw ValidationException::withMessages([
                    'encaissement' => __('This payment is attached to a fee: use the fee transfer instead.'),
                ]);
            }

            // Une ligne d'APPLICATION porte l'argent de son avance parente :
            // la déplacer laisserait le parent compter de l'argent dépensé
            // au nom de quelqu'un d'autre.
            if ($row->applied_from_encaissement_id !== null) {
                throw ValidationException::withMessages([
                    'encaissement' => __('This row is an advance allocation: its money belongs to the parent advance. Detach it first, then re-apply it.'),
                ]);
            }

            // Symétrique : une avance déjà (partiellement) appliquée a des
            // lignes filles au nom de l'ancien étudiant.
            if ($row->applications()->exists()) {
                throw ValidationException::withMessages([
                    'encaissement' => __('This advance has already been applied to fees: detach its allocations before moving it.'),
                ]);
            }

            if ($row->remboursements()->exists()) {
                throw ValidationException::withMessages([
                    'encaissement' => __('A refunded payment cannot be transferred.'),
                ]);
            }

            // Un chèque appartient à quelqu'un (`cheques.student_id`) —
            // même refus que le transfert de frais.
            if ($row->cheque_id !== null) {
                throw ValidationException::withMessages([
                    'encaissement' => __('This payment is backed by a tracked cheque, which belongs to its own owner: it cannot be transferred to another student.'),
                ]);
            }

            if ($fee->estMasque()) {
                throw ValidationException::withMessages([
                    'inscription_fee_id' => __('The « :frais » line of the target registration is hidden: money never lands on a fee that is no longer due.', [
                        'frais' => $fee->nom,
                    ]),
                ]);
            }

            if ($cible->student_id === $row->student_id) {
                throw ValidationException::withMessages([
                    'inscription' => __('This registration belongs to the same student. Use « Apply the advance » instead.'),
                ]);
            }

            if ($row->etablissement_id !== $cible->etablissement_id) {
                throw ValidationException::withMessages([
                    'inscription' => __('The target registration belongs to another centre. A fee can only be transferred within the same centre.'),
                ]);
            }

            $reste = round((float) $fee->montant - $fee->montantPaye(), 2);
            $montant = round((float) $row->montant, 2);

            if ($montant > $reste) {
                throw ValidationException::withMessages([
                    'inscription_fee_id' => __('The payment (:montant MAD) exceeds what the « :frais » line of the target registration still owes (:reste MAD).', [
                        'montant' => number_format($montant, 2, '.', ' '),
                        'frais' => $fee->nom,
                        'reste' => number_format(max(0.0, $reste), 2, '.', ' '),
                    ]),
                ]);
            }

            $ancienStudentId = $row->student_id;
            $ancienNom = $row->student?->nomComplet();

            // Les DEUX seules colonnes réécrites — journalisées par
            // Auditable, plus l'entrée métier ci-dessous.
            $row->update([
                'inscription_fee_id' => $fee->getKey(),
                'student_id' => $cible->student_id,
            ]);

            $this->recalculerStatutFee($fee);
            $cible->update([
                'montant_total' => $cible->fees()->whereNull('masque_le')->sum('montant') ?: null,
            ]);

            activity('encaissement')
                ->performedOn($row)
                ->event('avance_affectee_autre_etudiant')
                ->withProperties([
                    'montant' => number_format($montant, 2, '.', ''),
                    'motif' => $motif,
                    'ancien_etudiant_id' => $ancienStudentId,
                    'ancien_etudiant' => $ancienNom,
                    'nouvel_etudiant_id' => $cible->student_id,
                    'nouvel_etudiant' => $cible->student?->nomComplet(),
                    'nouvelle_inscription' => $cible->reference,
                    'nouveau_frais_id' => $fee->getKey(),
                    'nouveau_frais' => $fee->nom,
                    'etablissement_id' => $cible->etablissement_id,
                ])
                ->log(sprintf(
                    'Avance %s (%s MAD) affectée de %s au frais « %s » de %s (%s) - motif : %s',
                    $row->reference,
                    number_format($montant, 2, '.', ' '),
                    $ancienNom ?? '?',
                    $fee->nom,
                    $cible->student?->nomComplet() ?? '?',
                    $cible->reference,
                    $motif,
                ));

            return $row->refresh();
        });
    }

    /** Même définition que TransfererFraisVersAutreEtudiant::recalculerStatutFee(). */
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
