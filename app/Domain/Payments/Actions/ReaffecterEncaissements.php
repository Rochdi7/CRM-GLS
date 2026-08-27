<?php

declare(strict_types=1);

namespace App\Domain\Payments\Actions;

use App\Models\Encaissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use Illuminate\Support\Collection;
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
 * target inscription's « Frais de Juillet ». ⚠ THE FRAIS ALWAYS FOLLOWS THE
 * MONEY — moving a payment to another registration never strips its fee. When
 * the target registration has no counterpart (or only a masked / already-full
 * one), the fee line is RECREATED on it from the source fee (same nom, same
 * montant, same échéance, same remise trail) and the payment settles that.
 * Otherwise the operator was left with an orphan avance carrying no fee, and
 * the student's statement lost the line the money was paid against.
 *
 * The one case that still cannot be placed is a student not enrolled in the
 * target group at all: there is no registration to carry the fee. Those are
 * left untouched — never detached — and reported back by name.
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
     * @return array{deplaces: int, avances: int, montant: string, sansInscription: list<string>, fraisCrees: int}
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

            // ⚠ Decide what is placeable BEFORE detaching anything. A student
            // with no registration in the target group has nowhere for the fee
            // to go, so its payment must keep the fee it already has — the old
            // order detached every selected row first and only then discovered
            // it, leaving the money as an avance with no frais at all.
            $sansInscription = [];
            $aDeplacer = $encaissements->filter(function (Encaissement $e) use ($inscriptionParStudent, &$sansInscription): bool {
                if ($inscriptionParStudent->has($e->student_id)) {
                    return true;
                }

                $nom = trim(($e->student?->prenom ?? '').' '.($e->student?->nom ?? ''));

                if ($nom !== '' && ! in_array($nom, $sansInscription, true)) {
                    $sansInscription[] = $nom;
                }

                return false;
            })->values();

            // The source fee of each movable payment, kept BEFORE detaching:
            // it is the template the target fee line is recreated from when
            // the target registration has no counterpart.
            $fraisSource = [];

            foreach ($aDeplacer as $encaissement) {
                if ($encaissement->fee !== null) {
                    $fraisSource[$encaissement->id] = $encaissement->fee;
                }
            }

            // Detach per source inscription — ConvertirEncaissementsEnAvance
            // validates that every id it is given belongs to the inscription
            // it is called with, so the selection is grouped first.
            $parInscription = $aDeplacer
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
            $restes = count($sansInscription) > 0 ? $encaissements->count() - $aDeplacer->count() : 0;
            $montant = 0.0;
            $fraisCrees = 0;

            foreach ($aDeplacer as $encaissement) {
                /** @var Inscription $inscription */
                $inscription = $inscriptionParStudent->get($encaissement->student_id);
                $source = $fraisSource[$encaissement->id] ?? null;

                $avance = $encaissement->fresh();

                if ($avance === null) {
                    $restes++;

                    continue;
                }

                $restant = (float) $avance->montantRestant();

                if ($restant <= 0.0) {
                    $restes++;

                    continue;
                }

                $fee = $this->feeCible(
                    $inscription,
                    $source,
                    $restant,
                    $feesParInscription,
                    $fraisCrees,
                );

                if ($fee === null) {
                    $restes++;

                    continue;
                }

                $du = round((float) $fee->montant - $fee->montantPaye(), 2);
                $part = min($du, $restant);

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
                'fraisCrees' => $fraisCrees,
            ];
        });
    }

    /**
     * The fee line on the TARGET registration that this money belongs to.
     *
     * Preference order, all keyed on the fee NAME:
     *   1. an existing, visible fee of that name that still owes something,
     *   2. an existing MASKED fee of that name — unmasked rather than
     *      duplicated, so the student's statement keeps one line per fee,
     *   3. an existing fee of that name that is already fully paid — its
     *      montant is raised to cover the incoming money, because the amount
     *      the student actually paid for this fee in this group IS what is
     *      owed (the alternative is a second identical line),
     *   4. no counterpart at all ⇒ the line is RECREATED from the source fee,
     *      keeping nom / montant / échéance / remise / frais_id.
     *
     * Only a payment with no source fee at all (a pure avance selected by
     * hand) can come back null — there is nothing to name the new line after.
     */
    private function feeCible(
        Inscription $inscription,
        ?InscriptionFee $source,
        float $restant,
        Collection $feesParInscription,
        int &$fraisCrees,
    ): ?InscriptionFee {
        $nomFrais = mb_strtolower(trim((string) ($source?->nom ?? '')));

        if ($nomFrais === '') {
            return null;
        }

        /** @var Collection<int, InscriptionFee> $existantes */
        $existantes = $feesParInscription->get($inscription->id) ?? collect();
        $memeNom = $existantes->filter(
            fn (InscriptionFee $f): bool => mb_strtolower(trim($f->nom)) === $nomFrais
        );

        // 1. A visible line that still owes money takes it as-is.
        $fee = $memeNom->first(
            fn (InscriptionFee $f): bool => ! $f->estMasque()
                && round((float) $f->montant - $f->montantPaye(), 2) > 0.0
        );

        if ($fee !== null) {
            return $fee;
        }

        // 2. A masked line of that name is brought back rather than duplicated.
        $fee = $memeNom->first(fn (InscriptionFee $f): bool => $f->estMasque());

        if ($fee !== null) {
            $fee->update(['masque_le' => null]);
        }

        // 3. Otherwise reuse a saturated line of that name.
        $fee ??= $memeNom->first();

        if ($fee !== null) {
            $du = round((float) $fee->montant - $fee->montantPaye(), 2);

            // Raise the owed amount so the money keeps its own fee instead of
            // spilling into an orphan avance. The discount trail is left
            // alone: montant is the authoritative figure the fee is settled
            // against, montant_initial only documents how it was computed.
            if ($du < $restant) {
                $fee->update(['montant' => round((float) $fee->montant + ($restant - $du), 2)]);
            }

            return $fee->refresh();
        }

        // 4. Nothing of that name here — recreate the line from the source.
        $fraisCrees++;

        $nouvelle = InscriptionFee::create([
            'inscription_id' => $inscription->id,
            'frais_id' => $source?->frais_id,
            'nom' => (string) $source?->nom,
            'montant_initial' => $source?->montant_initial,
            'remise_pct' => $source?->remise_pct,
            'remise_montant' => $source?->remise_montant,
            'montant' => $source?->montant,
            // The source fee's own due date: this line is the same fee moved
            // to another registration, not a new one falling due today.
            'date_echeance' => $source?->date_echeance?->toDateString(),
            'note' => $source?->note,
        ]);

        // Keep the in-memory index in sync so a second payment of the same
        // student + same fee in this batch reuses this line instead of
        // creating a duplicate.
        $feesParInscription->put(
            $inscription->id,
            $existantes->push($nouvelle)->values()
        );

        return $nouvelle;
    }
}
