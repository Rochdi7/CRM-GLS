<?php

declare(strict_types=1);

namespace App\Domain\Groups\Actions;

use App\Domain\Payments\Actions\ConvertirEncaissementsEnAvance;
use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;

/**
 * Removes a catalog fee from a group and CASCADES that removal to every
 * inscription of the group — the group-level counterpart to
 * Registrations\Actions\BasculerVisibiliteFraisInscription, which does the
 * same thing for one registration at a time.
 *
 * Nothing is ever deleted (CLAUDE.md §11):
 *
 *  1. the `group_frais` pivot row is detached (the group stops offering the
 *     fee, so a future inscription no longer receives it — see
 *     GetGroupInscriptionFees);
 *  2. every matching InscriptionFee of the group's inscriptions is HIDDEN
 *     (`masque_le`), never deleted, so its payment history survives and the
 *     line can be restored;
 *  3. the payments that had settled those fees are DETACHED back into
 *     unallocated avances through ConvertirEncaissementsEnAvance — the money
 *     stays in the till (caisses.solde is untouched: only the allocation
 *     changes) and becomes re-applicable to any other fee of the same
 *     student via AppliquerAvance.
 *
 * Point 3 is the whole reason this exists: hiding a paid fee without
 * releasing its money would strand the student's dirhams on an invisible
 * line, where no screen could ever spend them again.
 *
 * restore() is the exact reverse — it re-attaches the pivot with the amount
 * / due date / classification the group carried, and un-hides the lines on
 * every inscription. It deliberately does NOT re-apply the freed avances:
 * money re-allocation is always an explicit, journaled decision
 * (AppliquerAvance), never a side effect of an edit.
 */
final class RetirerFraisGroupe
{
    public function __construct(
        private readonly ConvertirEncaissementsEnAvance $convertirEnAvance,
    ) {
    }

    /**
     * @return array{feesMasques: int, encaissementsConvertis: float}
     *         how many inscription fee lines were hidden and how much money
     *         went back to avances — surfaced to the user, since releasing
     *         money must never be silent.
     */
    public function handle(Group $group, int $fraisId): array
    {
        return DB::transaction(function () use ($group, $fraisId): array {
            $group->frais()->detach($fraisId);

            $inscriptionIds = $group->inscriptions()->pluck('id');

            $fees = InscriptionFee::query()
                ->whereIn('inscription_id', $inscriptionIds)
                ->where('frais_id', $fraisId)
                ->whereNull('masque_le')
                ->lockForUpdate()
                ->get();

            $montantLibere = 0.0;

            foreach ($fees->groupBy('inscription_id') as $inscriptionId => $feesDeLInscription) {
                $inscription = Inscription::query()->findOrFail($inscriptionId);

                // Payments still attached to the fees about to be hidden.
                // A refunded payment is refused by the converter (its money
                // already left the till), so it is filtered out here rather
                // than aborting the whole group edit for one row.
                $encaissements = Encaissement::query()
                    ->whereIn('inscription_fee_id', $feesDeLInscription->pluck('id'))
                    ->whereDoesntHave('remboursements')
                    ->get();

                if ($encaissements->isNotEmpty()) {
                    $this->convertirEnAvance->handle($inscription, $encaissements->pluck('id')->all());
                    $montantLibere += (float) $encaissements->sum('montant');
                }

                // Hidden AFTER the conversion: the converter re-checks each
                // fee belongs to the inscription and recomputes its statut,
                // and a fee is only really "no longer active" once its money
                // has been released.
                $feesDeLInscription->each(fn (InscriptionFee $fee) => $fee->update(['masque_le' => now()]));

                $this->recalculerMontantTotal($inscription);
            }

            return [
                'feesMasques' => $fees->count(),
                'encaissementsConvertis' => round($montantLibere, 2),
            ];
        });
    }

    /**
     * Re-attaches the fee to the group and un-hides it on every inscription
     * that had it hidden by handle(). Freed avances stay avances — see the
     * class docblock.
     *
     * @return int number of inscription fee lines restored
     */
    public function restore(Group $group, int $fraisId, float $montant, ?string $dateEcheance, ?string $classification): int
    {
        return DB::transaction(function () use ($group, $fraisId, $montant, $dateEcheance, $classification): int {
            $group->frais()->syncWithoutDetaching([
                $fraisId => [
                    'montant' => $montant,
                    'date_echeance' => $dateEcheance,
                    'classification' => $classification,
                ],
            ]);

            $inscriptionIds = $group->inscriptions()->pluck('id');

            $fees = InscriptionFee::query()
                ->whereIn('inscription_id', $inscriptionIds)
                ->where('frais_id', $fraisId)
                ->whereNotNull('masque_le')
                ->get();

            $fees->each(fn (InscriptionFee $fee) => $fee->update(['masque_le' => null]));

            Inscription::query()->whereIn('id', $fees->pluck('inscription_id')->unique())
                ->get()
                ->each(fn (Inscription $inscription) => $this->recalculerMontantTotal($inscription));

            return $fees->count();
        });
    }

    private function recalculerMontantTotal(Inscription $inscription): void
    {
        $inscription->update([
            'montant_total' => $inscription->fees()->whereNull('masque_le')->sum('montant') ?: null,
        ]);
    }
}
