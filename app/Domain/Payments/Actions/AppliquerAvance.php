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
 */
final class AppliquerAvance
{
    public function handle(Encaissement $avance, InscriptionFee $fee, float $montant): Encaissement
    {
        if (! $avance->isAvance()) {
            throw ValidationException::withMessages([
                'avance' => __('This payment is not an unallocated advance.'),
            ]);
        }

        $restant = $avance->montantRestant();

        if ($montant <= 0 || $montant > $restant) {
            throw ValidationException::withMessages([
                'montant' => __('The amount cannot exceed the advance\'s remaining balance.'),
            ]);
        }

        return DB::transaction(function () use ($avance, $fee, $montant): Encaissement {
            $application = Encaissement::create([
                'reference' => ReferenceGenerator::make('ENC', 'encaissements'),
                'student_id' => $avance->student_id,
                'inscription_fee_id' => $fee->id,
                'applied_from_encaissement_id' => $avance->id,
                'montant' => $montant,
                'methode' => $avance->methode,
                'date_paiement' => now()->toDateString(),
                'caisse_id' => $avance->caisse_id,
                'agent_id' => $avance->agent_id,
            ]);

            $this->recalculerStatutFee($fee);

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
