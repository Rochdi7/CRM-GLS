<?php

declare(strict_types=1);

namespace App\Services\Import\Concerns;

use App\Models\ImportBatch;
use App\Models\ImportRow;

/**
 * Batch counters are DERIVED from the rows, never accumulated.
 *
 * commit() runs in chunks and a row can be retried, so `$batch->x + $n` was
 * double-counting: a batch with 5 real failures reported 675 errors, and
 * skipped rows were never persisted at all. The row statuses are the single
 * source of truth; the counters are just a cached tally of them.
 */
trait TracksBatchProgress
{
    /** Recomputes every counter from the rows, then sets the batch status to match. */
    protected function syncBatchProgress(ImportBatch $batch, int $remaining): void
    {
        $counts = $batch->rows()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $inserted = (int) ($counts[ImportRow::STATUT_INSERE] ?? 0);
        $errors = (int) ($counts[ImportRow::STATUT_ECHEC_COMMIT] ?? 0);
        $skipped = (int) ($counts[ImportRow::STATUT_DOUBLON] ?? 0)
            + (int) ($counts[ImportRow::STATUT_IGNORE] ?? 0);

        $batch->update([
            'status' => $this->batchStatusFor($remaining, $errors),
            'inserted_rows' => $inserted,
            'error_rows' => $errors,
            'skipped_rows' => $skipped,
            'committed_at' => $remaining === 0 ? now() : $batch->committed_at,
        ]);
    }

    private function batchStatusFor(int $remaining, int $errors): string
    {
        if ($remaining > 0) {
            return ImportBatch::STATUT_COMMITTING;
        }

        return $errors > 0
            ? ImportBatch::STATUT_COMMITTED_WITH_ERRORS
            : ImportBatch::STATUT_COMMITTED;
    }
}
