<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Encaissement;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves already-recorded payments from the fees they sit on to the fees of
 * ANOTHER inscription — the bulk « changement de groupe » correction.
 *
 * Composed of the two existing money actions rather than touching rows
 * directly, so every invariant of CLAUDE.md §11 still holds:
 *   1. ConvertirEncaissementsEnAvance detaches each payment from its fee
 *      (row never deleted, source fees recomputed back to Non payé / Payé
 *      partiellement),
 *   2. AppliquerAvance spends each freed amount on the target inscription's
 *      matching fee — capped at what that fee still owes, and COPYING the
 *      original date_paiement so the money keeps the day it was received.
 *
 * `caisses.solde` never moves: the cash stayed in the till the whole time,
 * only its allocation changes. Nothing is refunded, nothing re-banked.
 *
 * Matching is by fee NAME: a payment on « Frais de Mars » lands on the target
 * inscription's « Frais de Mars ». A payment whose fee has no counterpart —
 * or whose counterpart is already fully paid — is left as an unallocated
 * avance on the student rather than forced somewhere wrong, and reported back
 * so the operator can place it by hand.
 *
 * Super-admin only (payments.reallocate): like groups.move-year this rewrites
 * which année money is booked against.
 */
final class ReaffecterEncaissements
{
    public function __construct(
        private readonly ConvertirEncaissementsEnAvance $convertir,
        private readonly AppliquerAvance $appliquer,
    ) {}

    /**
     * @param  list<int>  $encaissementIds
     * @return array{deplaces: int, avances: int, montant: string}
     */
    public function handle(array $encaissementIds, Inscription $cible): array
    {
        return DB::transaction(function () use ($encaissementIds, $cible): array {
            $encaissements = Encaissement::query()
                ->with(['fee.inscription'])
                ->whereIn('id', $encaissementIds)
                ->lockForUpdate()
                ->get();

            if ($encaissements->isEmpty()) {
                throw ValidationException::withMessages([
                    'encaissement_ids' => __('Select at least one payment to move.'),
                ]);
            }

            // One centre, always — a payment may cross années but never
            // centres (the money is in that centre's books).
            foreach ($encaissements as $encaissement) {
                if ((int) $encaissement->etablissement_id !== (int) $cible->etablissement_id) {
                    throw ValidationException::withMessages([
                        'inscription_id' => __('A payment cannot be moved to a registration of another centre.'),
                    ]);
                }
            }

            // Detach per source inscription — ConvertirEncaissementsEnAvance
            // validates that every id it is given belongs to the inscription
            // it is called with, so the selection is grouped first.
            $parInscription = $encaissements
                ->filter(fn (Encaissement $e): bool => $e->fee?->inscription_id !== null)
                ->groupBy(fn (Encaissement $e): int => (int) $e->fee->inscription_id);

            foreach ($parInscription as $inscriptionId => $lot) {
                $source = Inscription::findOrFail($inscriptionId);
                $this->convertir->handle($source, $lot->pluck('id')->map('intval')->all());
            }

            // Re-allocate each freed amount onto the target's fee of the SAME
            // name, capped at what that fee still owes.
            $feesCible = InscriptionFee::query()
                ->where('inscription_id', $cible->id)
                ->get()
                ->keyBy(fn (InscriptionFee $f): string => mb_strtolower(trim($f->nom)));

            $deplaces = 0;
            $restes = 0;
            $montant = 0.0;

            foreach ($encaissements as $encaissement) {
                $nom = mb_strtolower(trim((string) ($encaissement->fee?->nom ?? '')));
                $fee = $feesCible->get($nom);
                $avance = $encaissement->fresh();

                if ($fee === null || $avance === null) {
                    $restes++;

                    continue;
                }

                $du = round((float) $fee->montant - $fee->montantPaye(), 2);
                $part = min($du, (float) $avance->montantRestant());

                if ($part <= 0.0) {
                    $restes++;

                    continue;
                }

                $this->appliquer->handle($avance, $fee, $part);
                $deplaces++;
                $montant += $part;
            }

            return [
                'deplaces' => $deplaces,
                'avances' => $restes,
                'montant' => number_format($montant, 2, '.', ''),
            ];
        });
    }
}
