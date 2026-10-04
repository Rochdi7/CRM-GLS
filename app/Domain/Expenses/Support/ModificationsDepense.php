<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Support;

use App\Models\Activity;
use App\Models\Depense;
use Illuminate\Support\Carbon;

/**
 * « Modifiée le … » on the Dépenses operation trail — read from the audit
 * journal, never from `updated_at`.
 *
 * `updated_at` moves on EVERY write, and approving / refusing a dépense is
 * a write (`statut`, `approved_by`, `approved_at`, `motif_refus`). Comparing
 * it to `created_at` badged every approved row « modifiée » at the minute
 * of its approval — an auditor could no longer tell an expense whose
 * amount or description was rewritten from one that was simply validated.
 *
 * An edit is therefore an `updated` journal entry that changed at least one
 * column OUTSIDE the decision columns. One query for the whole page (§17:
 * never one per row).
 */
final class ModificationsDepense
{
    /** Columns a decision (approve / refuse) writes — not an edit. */
    private const COLONNES_DECISION = ['statut', 'approved_by', 'approved_at', 'motif_refus', 'updated_at'];

    /**
     * @param  list<int>  $ids
     * @return array<int, Carbon> dépense id ⇒ date of its last real edit
     */
    public static function dernieres(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count(self::COLONNES_DECISION), '?'));

        return Activity::query()
            ->where('subject_type', (new Depense)->getMorphClass())
            ->whereIn('subject_id', $ids)
            ->where('event', 'updated')
            ->whereRaw(
                "EXISTS (SELECT 1 FROM jsonb_object_keys(CASE WHEN jsonb_typeof(attribute_changes->'attributes') = 'object' THEN attribute_changes->'attributes' ELSE '{}'::jsonb END) AS k WHERE k NOT IN ({$placeholders}))",
                self::COLONNES_DECISION,
            )
            ->groupBy('subject_id')
            ->selectRaw('subject_id, MAX(created_at) AS modifie_le')
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $r): array => [(int) $r->subject_id => Carbon::parse($r->modifie_le)])
            ->all();
    }
}
