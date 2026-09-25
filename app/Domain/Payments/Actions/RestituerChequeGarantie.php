<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Cheque;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rend PHYSIQUEMENT un chèque de GARANTIE à son propriétaire, parce que
 * l'étudiant a réglé autrement (espèces, TPE, virement) — le cas réel :
 * il laisse un chèque en garantie, annonce la date à laquelle il apportera
 * l'argent, revient ce jour-là, paie en liquide et repart avec son papier.
 *
 * ⚠ Ce geste ne touche à AUCUN argent. Un chèque est un inventaire
 * OFF-LEDGER (voir le commentaire du modèle Cheque et de sa migration) :
 * `caisses.solde` n'a jamais bougé quand le chèque est entré, et ne bouge
 * pas quand il sort. L'encaissement qui l'a remplacé est une écriture
 * ordinaire, enregistrée séparément par EnregistrerEncaissement avec sa
 * propre méthode — il n'est PAS lié au chèque (`cheque_id` reste NULL :
 * ce chèque n'a rien financé, c'est tout l'intérêt de l'opération).
 *
 * Aucune colonne n'a été ajoutée : `retourne_le` / `retourne_par_id`
 * existaient déjà pour la restitution d'un chèque REJETÉ, et portent
 * exactement le même fait — « le papier a quitté l'école, par qui, quand ».
 * Le MOTIF vit dans la note (append, jamais écrasée — même convention que
 * AnnulerDepense et AnnulerInscription) et dans le journal d'audit, seul
 * endroit où l'on pourra plus tard expliquer pourquoi la garantie est
 * sortie.
 *
 * Cinq bornes, toutes vérifiées SOUS VERROU dans la transaction (§11 : un
 * contrôle lu avant `DB::transaction` sur le modèle en mémoire est un
 * double-clic qui passe deux fois) :
 *
 *   1. type = Garantie — un chèque « À déposer » se remet à la banque, il
 *      ne se rend pas ; son parcours est déjà couvert par updateStatut().
 *   2. statut = En possession — « Déposé » veut dire que le papier est à la
 *      banque : on ne peut pas rendre ce qu'on n'a plus en main. Un chèque
 *      REJETÉ garde son propre chemin, inchangé (voir ci-dessous).
 *   3. montantUtilise() == 0 — si le chèque a déjà financé un encaissement,
 *      le papier n'est plus à rendre : il a payé, et les lignes qui
 *      pointent dessus sont append-only.
 *   4. jamais deux fois — estRetourne() refuse la seconde.
 *   5. motif obligatoire — c'est ce que le journal conserve.
 *
 * La restitution d'un chèque REJETÉ (ChequeController@markRetourne) reste
 * telle quelle : c'est un autre fait (la banque a refusé, on rend le papier
 * sans valeur), avec ses propres conditions. Les deux écrivent les mêmes
 * colonnes, ce qui est voulu : la question à laquelle elles répondent est
 * la même.
 *
 * Écrit le 19/09/2026 — avant, un chèque de garantie réglé autrement restait
 * « En possession » pour toujours : il figurait encore dans l'inventaire des
 * garanties vivantes ET dans le menu « Payer avec un chèque », si bien que
 * la même feuille de papier, déjà rendue à la main, pouvait être dépensée
 * une seconde fois dans le CRM.
 */
final class RestituerChequeGarantie
{
    /**
     * @param  string  $motif  phrase française lue par un humain, conservée
     *                         dans la note du chèque et dans le journal
     */
    public function handle(Cheque $cheque, string $motif, Employee $restituePar): Cheque
    {
        $motif = trim($motif);

        if ($motif === '') {
            throw ValidationException::withMessages([
                'motif' => __('A reason is required to return a guarantee cheque.'),
            ]);
        }

        return DB::transaction(function () use ($cheque, $motif, $restituePar): Cheque {
            /** @var Cheque $verrouille */
            $verrouille = Cheque::query()
                ->whereKey($cheque->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouille->type !== Cheque::TYPE_GARANTIE) {
                throw ValidationException::withMessages([
                    'motif' => __('Only a guarantee cheque can be returned this way - a cheque to deposit follows the bank flow.'),
                ]);
            }

            if ($verrouille->statut !== Cheque::STATUT_EN_POSSESSION) {
                throw ValidationException::withMessages([
                    'motif' => __('Only a cheque still in hand can be returned to its owner.'),
                ]);
            }

            if ($verrouille->estRetourne()) {
                throw ValidationException::withMessages([
                    'motif' => __('This cheque has already been marked as returned.'),
                ]);
            }

            // Relu sous verrou : entre l'affichage de l'écran et le clic, le
            // chèque a pu financer un paiement (le menu « Payer avec un
            // chèque » l'offrait encore). Rendre le papier alors qu'il a
            // soldé un frais laisserait un encaissement pointant sur une
            // garantie qui n'est plus chez nous.
            $utilise = $verrouille->montantUtilise();

            if ($utilise > 0) {
                throw ValidationException::withMessages([
                    'motif' => __('This cheque has already been used to pay and can no longer be returned.'),
                ]);
            }

            $note = trim((string) $verrouille->note);
            $suffixe = '[RESTITUÉ] le '.now()->format('d/m/Y')
                .' - '.rtrim($motif, '.').'.';

            $verrouille->update([
                'retourne_le' => now(),
                'retourne_par_id' => $restituePar->id,
                'note' => $note === '' ? $suffixe : $note."\n".$suffixe,
            ]);

            // Auditable journalise déjà les colonnes changées, mais
            // « retourne_le : vide → 2026-09-19 12:04:11 » ne dit pas ce que
            // cela SIGNIFIE : une garantie de 5 000 DH a quitté l'école.
            // Le montant, le propriétaire, la banque et le motif appartiennent
            // à la même ligne que celle que lira un contrôleur — même
            // convention que l'entrée `cheque_statut` d'updateStatut().
            activity('cheque')
                ->performedOn($verrouille)
                ->event('cheque_restitue')
                ->withProperties([
                    'reference' => $verrouille->reference,
                    'montant' => number_format((float) $verrouille->montant, 2, '.', ''),
                    'numero_cheque' => $verrouille->numero_cheque,
                    'banque' => $verrouille->banque,
                    'proprietaire' => $verrouille->proprietaireLabel(),
                    'etudiant_id' => $verrouille->student_id,
                    'type' => $verrouille->type,
                    'date_echeance' => $verrouille->date_echeance?->toDateString(),
                    'motif' => $motif,
                    'restitue_par' => $restituePar->nomComplet(),
                ])
                ->log("Chèque de garantie {$verrouille->reference} restitué à son propriétaire");

            return $verrouille;
        });
    }
}
