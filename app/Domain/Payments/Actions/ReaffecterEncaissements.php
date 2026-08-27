<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves already-recorded payments onto the target GROUP — the bulk
 * « changement de groupe » correction.
 *
 * ⚠ The target is a GROUP, never a single registration: a selection normally
 * spans SEVERAL students (one whole group's Frais de Juillet, say), and each
 * student's money may only ever land on THAT student's own inscription in the
 * target group. Asking the operator to pick one registration made it possible
 * to aim a dozen students' payments at one of them — AppliquerAvance refuses
 * that (a fee must belong to the avance's student), so it could only ever have
 * failed mid-transaction with a raw error. Resolving the registration per
 * student removes the trap entirely (26/08/2026).
 *
 * Composed of the two existing money actions rather than touching rows
 * directly, so every invariant of CLAUDE.md §11 still holds:
 *   1. ConvertirEncaissementsEnAvance detaches each payment from its fee
 *      (row never deleted, source fees recomputed back to Non payé / Payé
 *      partiellement),
 *   2. AppliquerAvance spends each freed amount on the matching fee of the
 *      student's own registration — capped at what that fee still owes, and
 *      COPYING the original date_paiement so the money keeps the day it was
 *      received.
 *
 * `caisses.solde` never moves: the cash stayed in the till the whole time,
 * only its allocation changes. Nothing is refunded, nothing re-banked.
 *
 * Matching is by fee NAME: a payment on « Frais de Juillet » lands on the
 * target inscription's « Frais de Juillet ». A payment whose fee has no
 * counterpart — or whose student is not enrolled in the target group, or whose
 * counterpart is already fully paid — is left as an unallocated avance on its
 * own student rather than forced somewhere wrong, and reported back so the
 * operator can place it by hand.
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
     * @return array{deplaces: int, avances: int, montant: string, sansInscription: list<string>}
     */
    public function handle(array $encaissementIds, Group $cible): array
    {
        return DB::transaction(function () use ($encaissementIds, $cible): array {
            $encaissements = Encaissement::query()
                ->with(['fee.inscription', 'student:id,nom,prenom'])
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
                        'group_id' => __('A payment cannot be moved to a group of another centre.'),
                    ]);
                }
            }

            // Each student's OWN registration in the target group. A student
            // with none simply keeps his money as an avance (below).
            $inscriptionParStudent = Inscription::query()
                ->where('group_id', $cible->id)
                ->whereIn('student_id', $encaissements->pluck('student_id')->unique()->all())
                ->get()
                ->keyBy('student_id');

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

            $feesParInscription = InscriptionFee::query()
                ->whereIn('inscription_id', $inscriptionParStudent->pluck('id')->all())
                ->get()
                ->groupBy('inscription_id');

            $deplaces = 0;
            $restes = 0;
            $montant = 0.0;
            $sansInscription = [];

            foreach ($encaissements as $encaissement) {
                $inscription = $inscriptionParStudent->get($encaissement->student_id);

                if ($inscription === null) {
                    $restes++;
                    $nom = trim(($encaissement->student?->prenom ?? '').' '.($encaissement->student?->nom ?? ''));

                    if ($nom !== '' && ! in_array($nom, $sansInscription, true)) {
                        $sansInscription[] = $nom;
                    }

                    continue;
                }

                $nomFrais = mb_strtolower(trim((string) ($encaissement->fee?->nom ?? '')));
                $fee = ($feesParInscription->get($inscription->id) ?? collect())
                    ->first(fn (InscriptionFee $f): bool => mb_strtolower(trim($f->nom)) === $nomFrais);

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
                'sansInscription' => $sansInscription,
            ];
        });
    }
}
