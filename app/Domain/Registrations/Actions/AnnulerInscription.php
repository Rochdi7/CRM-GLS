<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Encaissement;
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
 *   - SCOPE_ALL          — every fee line not fully paid is removed.
 *   - SCOPE_OVERDUE_ONLY — only those due AFTER the end date; a line that
 *                          fell due while the student was still enrolled
 *                          stays owed, because it was genuinely earned.
 *
 * A fee that already received money is never deleted outright: like the
 * group-change flow, any Encaissement pointing at a removed line is
 * UNLINKED (inscription_fee_id = null) rather than deleted — money records
 * are append-only (CLAUDE.md §11) — turning the amount into an unallocated
 * avance that stays available to the student.
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
            ->where('statut', '!=', InscriptionFee::STATUT_PAYE);

        if ($scope === self::SCOPE_OVERDUE_ONLY) {
            $query->whereDate('date_echeance', '>', $dateFin);
        }

        $query->get()->each(function (InscriptionFee $fee): void {
            Encaissement::query()
                ->where('inscription_fee_id', $fee->id)
                ->lockForUpdate()
                ->get()
                ->each(fn (Encaissement $e) => $e->update(['inscription_fee_id' => null]));
            $fee->delete();
        });
    }
}
