<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Cheque;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
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
 * propre méthode — il n'est PAS financé par le chèque (`cheque_id` reste
 * NULL : ce chèque n'a rien financé, c'est tout l'intérêt de l'opération) ;
 * il est seulement NOMMÉ dans `restitution_encaissement_id` (borne 6).
 *
 * `retourne_le` / `retourne_par_id`
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
 *   6. (30/09/2026) le PAIEMENT QUI REMPLACE la garantie est désigné —
 *      espèces / TPE / virement, du même étudiant (ou, source « Parents »,
 *      d'un étudiant du centre dont ce parent est le responsable), daté au
 *      plus tôt du jour de réception du chèque, jamais déjà utilisé pour
 *      libérer une autre garantie. Tant qu'un tel paiement n'existe pas, la
 *      garantie RESTE en main : on ne rend le papier que contre un
 *      règlement. Il est gardé dans `restitution_encaissement_id` (un lien
 *      de traçabilité, pas `cheque_id` : ce paiement n'est pas financé par
 *      le chèque). L'écran et la vérification sous verrou lisent la MÊME
 *      requête, paiementsRemplacants().
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
    public function handle(Cheque $cheque, string $motif, Employee $restituePar, ?int $encaissementId = null): Cheque
    {
        if ($encaissementId === null) {
            throw ValidationException::withMessages([
                'encaissement_id' => __('Choose the payment (cash, card or transfer) that replaced this guarantee.'),
            ]);
        }

        $motif = trim($motif);

        if ($motif === '') {
            throw ValidationException::withMessages([
                'motif' => __('A reason is required to return a guarantee cheque.'),
            ]);
        }

        return DB::transaction(function () use ($cheque, $motif, $restituePar, $encaissementId): Cheque {
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

            /** @var Encaissement|null $remplacant */
            $remplacant = self::paiementsRemplacants($verrouille)
                ->whereKey($encaissementId)
                ->lockForUpdate()
                ->first();

            if ($remplacant === null) {
                throw ValidationException::withMessages([
                    'encaissement_id' => __('This payment cannot replace the guarantee: it must be a cash, card or transfer payment of the same student, made on or after the cheque was received, and not already used for another guarantee.'),
                ]);
            }

            $note = trim((string) $verrouille->note);
            $suffixe = '[RESTITUÉ] le '.now()->format('d/m/Y')
                .' - '.rtrim($motif, '.')
                .' (remplacé par '.$remplacant->reference.', '.$remplacant->methode.').';

            $verrouille->update([
                'retourne_le' => now(),
                'retourne_par_id' => $restituePar->id,
                'restitution_encaissement_id' => $remplacant->id,
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
                    'paiement_remplacant' => $remplacant->reference,
                    'paiement_remplacant_methode' => $remplacant->methode,
                    'paiement_remplacant_montant' => number_format((float) $remplacant->montant, 2, '.', ''),
                    'restitue_par' => $restituePar->nomComplet(),
                ])
                ->log("Chèque de garantie {$verrouille->reference} restitué à son propriétaire");

            return $verrouille;
        });
    }

    /**
     * Les paiements qui peuvent remplacer cette garantie. SEULE définition,
     * lue par l'écran (ChequeController@remplacements) et rejouée sous
     * verrou par handle().
     *
     * @return Builder<Encaissement>
     */
    public static function paiementsRemplacants(Cheque $cheque): Builder
    {
        return Encaissement::query()
            ->whereIn('methode', [
                Encaissement::METHODE_ESPECES,
                Encaissement::METHODE_TPE,
                Encaissement::METHODE_VIREMENT,
            ])
            ->whereNull('applied_from_encaissement_id')
            ->whereNull('cheque_id')
            ->whereDate('date_paiement', '>=', $cheque->date_reception?->toDateString() ?? '1900-01-01')
            ->whereNotIn('id', Cheque::query()
                ->whereNotNull('restitution_encaissement_id')
                ->whereKeyNot($cheque->getKey())
                ->select('restitution_encaissement_id'))
            ->where(function (Builder $q) use ($cheque): void {
                if ($cheque->student_id !== null) {
                    $q->where('student_id', $cheque->student_id);

                    return;
                }

                // Source « Parents » : le chèque ne porte que le nom du
                // parent ; ses enfants sont les étudiants du centre dont il
                // est le responsable enregistré.
                $q->whereIn('student_id', Student::query()
                    ->where('etablissement_id', $cheque->etablissement_id)
                    ->where('parent_nom', 'ilike', trim((string) $cheque->proprietaire_nom))
                    ->select('id'));
            });
    }
}
