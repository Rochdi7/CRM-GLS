<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import\Concerns;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use Illuminate\Http\Request;

/**
 * Preview/Result screens must never load every ImportRow at once — a real
 * legacy export is thousands of rows, and serializing them all into one
 * Inertia payload exhausted PHP's memory limit (blank error modal on
 * upload). Rows are paginated + filterable server-side instead; only the
 * (cheap) selectable row IDs are sent in full, because the chunked commit
 * loop and "select all" need the whole set, not just the current page.
 */
trait BuildsImportPreview
{
    private const int PREVIEW_PER_PAGE = 50;

    /**
     * @return array<string, mixed> Inertia props shared by every module's Preview screen
     */
    protected function previewProps(Request $request, ImportBatch $batch): array
    {
        $statusFilter = (string) $request->string('status');

        if (! in_array($statusFilter, ImportRow::STATUTS, true)) {
            $statusFilter = '';
        }

        $rowsQuery = $batch->rows()->orderBy('source_row_number');

        if ($statusFilter !== '') {
            $rowsQuery->where('status', $statusFilter);
        }

        return [
            'batch' => $batch->load(['etablissement', 'anneeScolaire']),
            'rows' => $rowsQuery
                ->paginate(self::PREVIEW_PER_PAGE)
                ->withQueryString(),
            'statusCounts' => $this->statusCounts($batch),
            // Only NOUVEAU rows are pre-checked. CONFLIT rows stay
            // selectable (the operator can still resolve one by hand) but
            // are never swept into the default selection — committing an
            // unresolved row can only ever fail, and on a real export that
            // meant thousands of guaranteed failures in one click.
            'selectableRowIds' => $batch->rows()
                ->where('status', ImportRow::STATUT_NOUVEAU)
                ->orderBy('source_row_number')
                ->pluck('id'),
            'conflictRowIds' => $batch->rows()
                ->where('status', ImportRow::STATUT_CONFLIT)
                ->orderBy('source_row_number')
                ->pluck('id'),
            // Previously-failed rows can be re-queued once their cause is
            // fixed. They are NOT commit-eligible as-is (that made the
            // progress loop spin forever) — the Preview screen posts them to
            // the retry endpoint, which flips them back to CONFLIT.
            'failedRowIds' => $batch->rows()
                ->whereIn('status', ImportRow::RETRYABLE_STATUTS)
                ->orderBy('source_row_number')
                ->pluck('id'),
            'filters' => ['status' => $statusFilter],
        ];
    }

    /**
     * @return array<string, mixed> Inertia props shared by every module's Result screen
     */
    protected function resultProps(Request $request, ImportBatch $batch): array
    {
        return [
            'batch' => $batch->load(['etablissement', 'anneeScolaire']),
            'failedRows' => $batch->rows()
                ->where('status', ImportRow::STATUT_ECHEC_COMMIT)
                ->orderBy('source_row_number')
                ->paginate(self::PREVIEW_PER_PAGE, ['*'], 'failed_page')
                ->withQueryString(),
            // "Ignorées" used to be a bare number with nothing behind it —
            // the operator could see 4 rows had been skipped but not which
            // ones or why. Every skipped row now carries a reason (set at
            // analyze time by each importer's duplicateReason()) and is
            // listed here so the count can actually be audited.
            'skippedRows' => $batch->rows()
                ->whereIn('status', [ImportRow::STATUT_DOUBLON, ImportRow::STATUT_IGNORE])
                ->orderBy('source_row_number')
                ->paginate(self::PREVIEW_PER_PAGE, ['*'], 'skipped_page')
                ->withQueryString(),
            'statusCounts' => $this->statusCounts($batch),
        ];
    }

    /**
     * Sums the parsed `montant` out of import_rows.raw in SQL — the
     * Encaissements reconciliation total, which used to be tallied
     * client-side over every row (impossible now that rows are paginated,
     * and a memory hazard even before that).
     */
    protected function rawMontantTotal(ImportBatch $batch): string
    {
        $total = $batch->rows()
            ->selectRaw("coalesce(sum((raw->>'montant')::numeric), 0) as total")
            ->value('total');

        return number_format((float) $total, 2, '.', '');
    }

    /** Same SQL sum as rawMontantTotal(), but only over rows actually committed. */
    protected function importedMontantTotal(ImportBatch $batch): string
    {
        $total = $batch->rows()
            ->where('status', ImportRow::STATUT_INSERE)
            ->selectRaw("coalesce(sum((raw->>'montant')::numeric), 0) as total")
            ->value('total');

        return number_format((float) $total, 2, '.', '');
    }

    /**
     * One grouped COUNT instead of loading every row just to tally them.
     *
     * @return array<string, int>
     */
    private function statusCounts(ImportBatch $batch): array
    {
        return $batch->rows()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }
}
