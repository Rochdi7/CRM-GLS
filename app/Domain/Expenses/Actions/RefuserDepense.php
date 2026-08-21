<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Actions;

use App\Models\Depense;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * REFUSE step of the dépense request flow.
 *
 * No money ever moves here — a pending dépense never debited the till, so
 * refusing it only records the decision and its motive. The row is KEPT
 * (never deleted): a refused expense is part of the audit trail, exactly
 * like a cancelled transfer.
 */
final class RefuserDepense
{
    public function handle(Depense $depense, Employee $refusedBy, ?string $motif = null): Depense
    {
        return DB::transaction(function () use ($depense, $refusedBy, $motif): Depense {
            /** @var Depense $locked */
            $locked = Depense::query()->whereKey($depense->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isDecided()) {
                throw ValidationException::withMessages([
                    'statut' => __('Cette dépense a déjà été traitée.'),
                ]);
            }

            $locked->update([
                'statut' => Depense::STATUT_REFUSEE,
                'approved_by' => $refusedBy->id,
                'approved_at' => now(),
                'motif_refus' => $motif,
            ]);

            return $locked;
        });
    }
}
