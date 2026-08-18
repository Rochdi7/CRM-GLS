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
            'selectableRowIds' => $batch->rows()
                ->whereIn('status', ImportRow::SELECTABLE_STATUTS)
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
                ->paginate(self::PREVIEW_PER_PAGE)
                ->withQueryString(),
            'statusCounts' => $this->statusCounts($batch),
        ];
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
