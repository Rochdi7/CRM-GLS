<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Domain\Shared\Support\ReferenceGenerator;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Applies part (or all) of an unallocated avance to a specific fee — creates
 * a SECOND Encaissement row (fee-targeted, applied_from_encaissement_id set
 * back to the avance) rather than editing the avance itself: money records
 * are append-only (CLAUDE.md §11). caisses.solde is untouched here — the
 * money already arrived into the till when the avance was originally
 * recorded; only its allocation changes.
 *
 * Every guard runs INSIDE the transaction on rows re-read under
 * lockForUpdate(): the avance's remaining balance and the fee's remaining
 * due are both "read then insert" checks, so two concurrent applies (a
 * double-click, two tabs) would otherwise each see the full balance and
 * together spend it twice.
 */
final class AppliquerAvance
{
    public function handle(Encaissement $avance, InscriptionFee $fee, float $montant): Encaissement
    {
        return DB::transaction(function () use ($avance, $fee, $montant): Encaissement {
            $avance = Encaissement::query()->whereKey($avance->getKey())->lockForUpdate()->firstOrFail();
            $fee = InscriptionFee::query()->with('inscription')->whereKey($fee->getKey())->lockForUpdate()->firstOrFail();

            if (! $avance->isAvance()) {
                throw ValidationException::withMessages([
                    'avance' => __('This payment is not an unallocated advance.'),
                ]);
            }

            // The fee must belong to the SAME student as the avance: a
            // tampered fee_id would otherwise settle another student's fee
            // with this student's money, leaving a row whose student_id and
            // fee disagree.
            if ($fee->inscription === null || $fee->inscription->student_id !== $avance->student_id) {
                throw ValidationException::withMessages([
                    'fee_id' => __('This fee does not belong to the student of this advance.'),
                ]);
            }

            if ($fee->estMasque()) {
                throw ValidationException::withMessages([
                    'fee_id' => __('This fee is no longer active.'),
                ]);
            }

            $restant = $avance->montantRestant();
            $montant = round($montant, 2);

            if ($montant <= 0 || $montant > $restant) {
                throw ValidationException::withMessages([
                    'montant' => __('The amount cannot exceed the advance\'s remaining balance.'),
                ]);
            }

            $resteFee = round(max(0.0, (float) $fee->montant - $fee->montantPaye()), 2);

            if ($montant > $resteFee) {
                throw ValidationException::withMessages([
                    'montant' => __('The amount cannot exceed the remaining balance of this fee.'),
                ]);
            }

            $application = Encaissement::create([
                'reference' => ReferenceGenerator::make('ENC', 'encaissements'),
                'student_id' => $avance->student_id,
                'etablissement_id' => $avance->etablissement_id,
                'inscription_fee_id' => $fee->id,
                'applied_from_encaissement_id' => $avance->id,
                'montant' => $montant,
                'methode' => $avance->methode,
                // ⚠ The ORIGINAL payment date, never now(). The money reached
                // the school when the avance was collected; applying it to a
                // fee only re-allocates it (the till does not move here — see
                // the docblock). Stamping today would rewrite when GLS was
                // paid: the receipt, the fee's payment history and every
                // date-windowed report (journal de caisse, année scolaire
                // range, retards) would place a March payment in August, and
                // an avance converted from an old inscription would silently
                // migrate into the current academic year.
                'date_paiement' => $avance->date_paiement,
                'caisse_id' => $avance->caisse_id,
                'agent_id' => $avance->agent_id,
            ]);

            $this->recalculerStatutFee($fee);

            // The till does not move here, so no CaisseLedger entry — but the
            // ALLOCATION is precisely what an avance investigation follows:
            // who decided this student's unallocated money should settle this
            // particular fee, and how much of the avance is left afterwards.
            // Without this line the journal would show a new payment row with
            // no trace of the advance it consumed.
            activity('encaissement')
                ->performedOn($application)
                ->event('avance_applied')
                ->withProperties([
                    'avance_reference' => $avance->reference,
                    'avance_id' => $avance->id,
                    'montant' => number_format($montant, 2, '.', ''),
                    'avance_restant_avant' => number_format($restant, 2, '.', ''),
                    'avance_restant_apres' => number_format($restant - $montant, 2, '.', ''),
                    'frais' => $fee->nom,
                    'frais_id' => $fee->id,
                    'etudiant_id' => $avance->student_id,
                    'caisse_id' => $avance->caisse_id,
                ])
                ->log("Avance {$avance->reference} appliquée au frais « {$fee->nom} »");

            return $application;
        });
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
