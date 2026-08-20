<?php

declare(strict_types=1);

namespace App\Domain\Finance\Support;

use App\Models\Caisse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The ONE way a till balance may move — and the reason every movement is
 * auditable (CLAUDE.md §11, docs/audit-journal.md).
 *
 * Why this exists: `caisses.solde` is a stored number with no ledger table
 * behind it (gls-crm-schema.md §10, a deliberate trade-off). Every action used
 * to move it with `Caisse::query()->increment('solde', …)`, which is raw SQL —
 * it never loads the model, so Eloquent fires no events and the audit journal
 * recorded NOTHING. The payment row was logged, but the balance change it
 * caused was invisible. For a CRM where "money is everything" that is exactly
 * the hole a fraud would slip through: adjust a till, and the trail shows only
 * that some payment existed, never that the balance moved or by how much.
 *
 * Every movement now goes through here and writes an `caisse` journal entry
 * carrying the FULL arithmetic — balance before, delta, balance after, and
 * what caused it — so a caisse can be verified line by line without
 * recomputing anything from the source tables.
 *
 * ⚠ Never call `increment('solde')` / `decrement('solde')` / a raw update on
 * that column again. A movement that skips this class is a movement nobody
 * can audit.
 *
 * Concurrency: the row is locked FOR UPDATE and re-read inside the caller's
 * transaction, so the recorded `solde_avant`/`solde_apres` are the real
 * values that surrounded this change, not a stale read.
 */
final class CaisseLedger
{
    /** Money coming IN (encaissement, transfer destination). */
    public const SENS_CREDIT = 'credit';

    /** Money going OUT (dépense, remboursement, transfer source, reversal). */
    public const SENS_DEBIT = 'debit';

    /**
     * Move a till balance and journal the movement.
     *
     * @param  Caisse|int  $caisse       the till (id accepted so callers need no extra query)
     * @param  float       $montant      always POSITIVE; direction comes from $sens
     * @param  string      $sens         self::SENS_CREDIT | self::SENS_DEBIT
     * @param  string      $motif        French, human: "Encaissement ENC-2026-0042"
     * @param  Model|null  $source       the record that caused it (encaissement, dépense…)
     * @param  array<string, mixed>  $extra  additional context for the journal entry
     */
    public function move(
        Caisse|int $caisse,
        float $montant,
        string $sens,
        string $motif,
        ?Model $source = null,
        array $extra = [],
    ): void {
        if ($montant <= 0.0) {
            // A zero/negative movement is always a caller bug: the direction is
            // carried by $sens, so a negative amount would silently invert it.
            throw new \InvalidArgumentException(
                'Un mouvement de caisse doit porter un montant strictement positif.',
            );
        }

        $caisseId = $caisse instanceof Caisse ? $caisse->id : $caisse;

        DB::transaction(function () use ($caisseId, $montant, $sens, $motif, $source, $extra): void {
            /** @var Caisse $locked */
            $locked = Caisse::query()->whereKey($caisseId)->lockForUpdate()->firstOrFail();

            $avant = (float) $locked->solde;
            $delta = $sens === self::SENS_CREDIT ? $montant : -$montant;
            $apres = round($avant + $delta, 2);

            // Written through the model (not increment()) so the balance column
            // also lands in the model's own `updated` audit entry.
            $locked->solde = number_format($apres, 2, '.', '');
            $locked->save();

            activity('caisse')
                ->performedOn($locked)
                ->event('solde_movement')
                ->withProperties([
                    'caisse' => $locked->nom,
                    'sens' => $sens === self::SENS_CREDIT ? 'Entrée' : 'Sortie',
                    'montant' => number_format($montant, 2, '.', ''),
                    'solde_avant' => number_format($avant, 2, '.', ''),
                    'solde_apres' => number_format($apres, 2, '.', ''),
                    'motif' => $motif,
                    'origine_type' => $source !== null ? $source::class : null,
                    'origine_id' => $source?->getKey(),
                    'origine_reference' => $source?->getAttribute('reference'),
                    ...$extra,
                ])
                ->log($sens === self::SENS_CREDIT
                    ? "Entrée en caisse : {$motif}"
                    : "Sortie de caisse : {$motif}");
        });
    }

    /** Convenience wrapper — money in. */
    public function credit(
        Caisse|int $caisse,
        float $montant,
        string $motif,
        ?Model $source = null,
        array $extra = [],
    ): void {
        $this->move($caisse, $montant, self::SENS_CREDIT, $motif, $source, $extra);
    }

    /** Convenience wrapper — money out. */
    public function debit(
        Caisse|int $caisse,
        float $montant,
        string $motif,
        ?Model $source = null,
        array $extra = [],
    ): void {
        $this->move($caisse, $montant, self::SENS_DEBIT, $motif, $source, $extra);
    }
}
