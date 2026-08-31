<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Full replacement of an existing inscription's VISIBLE fee lines, in ONE
 * transaction — the edit-modal counterpart to the create form's fee-line
 * table (InscriptionController::store()), now made live for the first time
 * (registrations.manage-fees was previously only checked by a dead
 * controller — see docs/phase-9-inscriptions-audit.md §12 point 1).
 *
 * Lines carrying an `id` update that InscriptionFee row (amount/discount/
 * date/note only — statut is always recomputed from actual payments, never
 * trusted from the client); lines with no `id` are created; any VISIBLE
 * existing row absent from the submitted set is deleted (hidden rows —
 * masque_le set — are simply never sent back by the client, since fees()
 * only returns visible ones, and must NOT be swept up by this "omitted =
 * delete" comparison — hiding/restoring a fee is MasquerFraisInscription/
 * RestaurerFraisInscription's job, never a hard delete). Deliberately
 * unrestricted on visible rows — a fee already fully or partially paid can
 * still have its montant changed or be removed (per product decision).
 * Removing a PAID line releases its payments into unallocated avances first
 * (ConvertirEncaissementsEnAvance, the same release RetirerFraisGroupe
 * performs), so the money stays on the student and re-applicable instead of
 * blocking the edit — until 31/08/2026 the delete simply hit the
 * encaissements FK-restrict and the whole edit was refused.
 */
final class MettreAJourFraisInscription
{
    public function __construct(
        private readonly ConvertirEncaissementsEnAvance $convertirEnAvance,
    ) {}

    /**
     * @param  list<array{id?: int, frais_id?: ?int, nom: string, montant_initial?: ?float, remise_pct?: ?float, remise_montant?: ?float, date_echeance?: ?string, note?: ?string}>  $lines
     */
    public function handle(Inscription $inscription, array $lines): Inscription
    {
        try {
            return DB::transaction(function () use ($inscription, $lines): Inscription {
                $keptIds = [];

                foreach ($lines as $line) {
                    $initial = (float) ($line['montant_initial'] ?? 0);
                    $remisePct = isset($line['remise_pct']) && $line['remise_pct'] !== null ? (float) $line['remise_pct'] : null;
                    $remiseMontant = isset($line['remise_montant']) && $line['remise_montant'] !== null ? (float) $line['remise_montant'] : null;
                    $montant = InscriptionFee::computeMontant($initial, $remisePct, $remiseMontant);

                    $existing = isset($line['id'])
                        ? InscriptionFee::query()->where('inscription_id', $inscription->id)->findOrFail($line['id'])
                        : null;

                    $attributes = [
                        'inscription_id' => $inscription->id,
                        'frais_id' => $line['frais_id'] ?? null,
                        'nom' => $line['nom'],
                        'montant_initial' => $initial,
                        'remise_pct' => $remisePct,
                        'remise_montant' => $remiseMontant,
                        'montant' => $montant,
                        // date_echeance is NOT NULL — an omitted value keeps
                        // the existing row's date on update, or defaults to
                        // today for a brand-new line (matches store()'s own
                        // `?? now()->toDateString()` fallback).
                        'date_echeance' => $line['date_echeance']
                            ?? $existing?->date_echeance?->toDateString()
                            ?? now()->toDateString(),
                        'note' => $line['note'] ?? null,
                    ];

                    if ($existing !== null) {
                        // Money already received on the line is the floor:
                        // pricing it below the paid amount would make the
                        // surplus vanish instead of becoming an avance (DB-06).
                        $paye = $existing->montantPaye();
                        if ($montant + 0.005 < $paye) {
                            throw ValidationException::withMessages([
                                'fee_lines' => __('A fee cannot be priced below the amount already paid on it (:paye DH).', ['paye' => number_format($paye, 2, '.', '')]),
                            ]);
                        }

                        $existing->update($attributes);
                        $fee = $existing;
                    } else {
                        // The same catalog fee already exists on this
                        // registration as a hidden line: adding it again would
                        // duplicate it the day the group restores it (R-07).
                        if (! empty($attributes['frais_id'])
                            && $inscription->fees()->where('frais_id', $attributes['frais_id'])->whereNotNull('masque_le')->exists()) {
                            throw ValidationException::withMessages([
                                'fee_lines' => __('This fee already exists on the registration as a hidden line — restore it instead of adding it again.'),
                            ]);
                        }

                        $fee = $inscription->fees()->create($attributes);
                    }

                    $this->recalculerStatut($fee);
                    $keptIds[] = $fee->id;
                }

                $supprimees = $inscription->fees()
                    ->whereNull('masque_le')
                    ->whereNotIn('id', $keptIds)
                    ->get();

                // ⚠ Money on a removed line becomes an AVANCE, it is never
                // lost and never blocks the edit (31/08/2026). Before this,
                // the delete below hit the encaissements FK-restrict and the
                // whole edit was refused with « ce frais a des paiements » —
                // so a fee added by mistake on a student who had already paid
                // could not be removed at all, and the money had no way back
                // into the avance pool. This is the SAME release the group
                // flow performs (RetirerFraisGroupe): detach the payments,
                // leaving them re-applicable, THEN drop the line. A refunded
                // payment is refused by the converter — its money already
                // left the till — so it is filtered out here rather than
                // aborting the edit.
                if ($supprimees->isNotEmpty()) {
                    $encaissements = Encaissement::query()
                        ->whereIn('inscription_fee_id', $supprimees->pluck('id'))
                        ->whereDoesntHave('remboursements')
                        ->pluck('id')
                        ->all();

                    if ($encaissements !== []) {
                        $this->convertirEnAvance->handle($inscription, $encaissements);
                    }
                }

                $supprimees->each(fn (InscriptionFee $fee) => $fee->delete());

                $inscription->update([
                    'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
                ]);

                return $inscription->fresh('fees');
            });
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'fee_lines' => __('One of the removed fees has payments and cannot be deleted.'),
            ]);
        }
    }

    private function recalculerStatut(InscriptionFee $fee): void
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
