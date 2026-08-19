<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Import;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Services\Context\CurrentContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ImportBatchController extends Controller
{
    public function index(Request $request, CurrentContext $context): Response
    {
        $this->authorize('viewAny', ImportBatch::class);

        return Inertia::render('Backoffice/Import/Index', [
            'recentBatches' => ImportBatch::query()
                ->with(['etablissement', 'anneeScolaire', 'createdBy'])
                ->orderByDesc('created_at')
                ->limit(20)
                ->get(),
            // Hides the redundant Centre column once the context switcher is
            // on a single center (CLAUDE.md §5 centre-filter rule).
            'centerLocked' => ! $context->isAllCenters(),
        ]);
    }
}
