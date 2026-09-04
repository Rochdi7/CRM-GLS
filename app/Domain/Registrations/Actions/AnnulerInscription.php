<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Annuler l'inscription" — Active -> Annulée with a mandatory reason, an
 * end date, and an optional cleanup of the fee lines the student will now
 * never owe.
 *
 * The unpaid-fee scopes are deliberately the SAME two the group-change flow
 * offers (ChangerGroupeInscription::SCOPES), with the same meaning, so a
 * cancellation and a group change dispose of leftover fees identically:
 *
 *   - SCOPE_ALL          — toute ligne n'ayant jamais rien reçu est retirée.
 *   - SCOPE_OVERDUE_ONLY — seulement celles échéant APRÈS la date de fin ;
 *                          une ligne échue alors que l'étudiant était encore
 *                          inscrit reste due, elle a été réellement gagnée.
 *
 * ⚠ Une ligne qui a reçu le moindre dirham n'est JAMAIS retirée — ni un
 * frais « Payé », ni un « Payé partiellement » (décidé le 04/09/2026). Le
 * critère n'est donc pas le statut mais l'absence d'encaissement : un statut
 * peut avoir dérivé, un encaissement non. Un frais partiellement payé reste
 * dû pour son reste, parce que l'étudiant a commencé à payer cette
 * prestation ; c'est un remboursement, pas une suppression de créance, qui
 * lui rendrait cet argent.
 *
 * Corollaire : cette action ne détache et ne supprime aucun encaissement.
 * La règle §11 « retirer un frais payé libère son argent en avance » n'est
 * pas contournée — elle est sans objet, puisque aucun frais portant de
 * l'argent n'est retiré ici.
 */
final class AnnulerInscription
{
    public const SCOPE_OVERDUE_ONLY = ChangerGroupeInscription::SCOPE_OVERDUE_ONLY;

    public const SCOPE_ALL = ChangerGroupeInscription::SCOPE_ALL;

    public const SCOPES = ChangerGroupeInscription::SCOPES;

    public function handle(
        Inscription $inscription,
        string $motif,
        string $dateFin,
        ?string $unpaidFeesScope,
        ?string $note,
    ): Inscription {
        if ($inscription->statut !== Inscription::STATUT_ACTIVE) {
            throw ValidationException::withMessages([
                'statut' => __('This status change is not allowed from the current status.'),
            ]);
        }

        return DB::transaction(function () use ($inscription, $motif, $dateFin, $unpaidFeesScope, $note): Inscription {
            if ($unpaidFeesScope !== null) {
                $this->removeUnpaidFees($inscription, $unpaidFeesScope, $dateFin);
            }

            $inscription->update([
                'statut' => Inscription::STATUT_ANNULEE,
                'motif_annulation' => $motif,
                'date_fin' => $dateFin,
                // Appended, never overwritten: the note already on the row is
                // the enrollment's own, and losing it to record a cancellation
                // comment would destroy information the user did not ask to
                // remove.
                'note' => $this->appendNote($inscription->note, $note),
                // Removing fee lines changes what is owed, so the stored total
                // has to follow. Recomputed from the surviving VISIBLE lines,
                // the same expression updateFees()/ChangerGroupeInscription use.
                'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
            ]);

            return $inscription;
        });
    }

    private function appendNote(?string $existing, ?string $added): ?string
    {
        $added = $added !== null ? trim($added) : '';

        if ($added === '') {
            return $existing;
        }

        return trim(($existing ?? '') === '' ? $added : $existing."\n".$added);
    }

    /**
     * Identical rules to ChangerGroupeInscription::removeUnpaidFees() — see
     * that method for why payments are unlinked one model at a time (a
     * query-builder update fires no Auditable event, and a silent
     * detachment of money is exactly what an audit must be able to see).
     */
    private function removeUnpaidFees(Inscription $inscription, string $scope, string $dateFin): void
    {
        $query = $inscription->fees()
            ->where('statut', '!=', InscriptionFee::STATUT_PAYE)
            // Le seul critère sûr : aucun argent reçu. Le statut peut avoir
            // dérivé, un encaissement non.
            ->whereDoesntHave('encaissements');

        if ($scope === self::SCOPE_OVERDUE_ONLY) {
            $query->whereDate('date_echeance', '>', $dateFin);
        }

        // Masqué, jamais supprimé : la ligne et son historique restent, et un
        // « Restaurer » la ramène si l'annulation était une erreur — même
        // mécanique que la corbeille du modal
        // (BasculerVisibiliteFraisInscription::hide).
        $query->get()->each(function (InscriptionFee $fee): void {
            $fee->update([
                'masque_le' => now(),
                'masque_origine' => InscriptionFee::MASQUE_ORIGINE_MANUEL,
            ]);
        });
    }
}
