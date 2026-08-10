<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Full replacement of an existing inscription's fee lines, in ONE
 * transaction — the edit-modal counterpart to the create form's fee-line
 * table (InscriptionController::store()), now made live for the first time
 * (registrations.manage-fees was previously only checked by a dead
 * controller — see docs/phase-9-inscriptions-audit.md §12 point 1).
 *
 * Lines carrying an `id` update that InscriptionFee row (amount/discount/
 * date/note only — statut is always recomputed from actual payments, never
 * trusted from the client); lines with no `id` are created; any existing
 * row absent from the submitted set is deleted. Deliberately unrestricted —
 * a fee already fully or partially paid can still have its montant changed
 * or be removed (per product decision); removing a line that has payments
 * hits the same encaissements FK-restrict the standalone destroy() already
 * relies on, surfaced here as a field error instead of a 500.
 */
final class MettreAJourFraisInscription
{
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
                        $existing->update($attributes);
                        $fee = $existing;
                    } else {
                        $fee = $inscription->fees()->create($attributes);
                    }

                    $this->recalculerStatut($fee);
                    $keptIds[] = $fee->id;
                }

                $inscription->fees()
                    ->whereNotIn('id', $keptIds)
                    ->get()
                    ->each(fn (InscriptionFee $fee) => $fee->delete());

                $inscription->update([
                    'montant_total' => $inscription->fees()->sum('montant') ?: null,
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
