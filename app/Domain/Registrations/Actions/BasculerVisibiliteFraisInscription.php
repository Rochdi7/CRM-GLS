<?php

declare(strict_types=1);

namespace App\Domain\Registrations\Actions;

use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Validation\ValidationException;

/**
 * Hides or restores a single fee line on an inscription — the edit modal's
 * "Frais de cette inscription" trash icon no longer hard-deletes (that used
 * to go through MettreAJourFraisInscription's "omitted = delete" sweep,
 * blocked only by the encaissements FK-restrict). Hiding just sets
 * masque_le; the row and its payment history are never touched, so a paid
 * or partially paid fee can be hidden too (unlike the old hard-delete path,
 * which needed the FK-restrict to refuse that case). montant_total is
 * recomputed from the still-visible fees either way.
 */
final class BasculerVisibiliteFraisInscription
{
    public function hide(Inscription $inscription, InscriptionFee $fee): void
    {
        $this->assertBelongsTo($inscription, $fee);

        $fee->update(['masque_le' => now(), 'masque_origine' => InscriptionFee::MASQUE_ORIGINE_MANUEL]);

        $this->recalculerMontantTotal($inscription);
    }

    public function restore(Inscription $inscription, InscriptionFee $fee): void
    {
        $this->assertBelongsTo($inscription, $fee);

        $fee->update(['masque_le' => null, 'masque_origine' => null]);

        $this->recalculerMontantTotal($inscription);
    }

    private function assertBelongsTo(Inscription $inscription, InscriptionFee $fee): void
    {
        if ($fee->inscription_id !== $inscription->id) {
            throw ValidationException::withMessages([
                'fee' => __('This fee does not belong to this registration.'),
            ]);
        }
    }

    private function recalculerMontantTotal(Inscription $inscription): void
    {
        $inscription->update([
            'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
        ]);
    }
}
