<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Groups\Queries\GetGroupsHistorique;
use App\Http\Controllers\Controller;
use App\Services\Context\CurrentContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only archive (schema §7): rows are created exclusively by
 * Group::archiverCommeTermine() — no create/update/delete here, ever.
 */
final class GroupHistoriqueController extends Controller
{
    public function index(Request $request, GetGroupsHistorique $getGroupsHistorique, CurrentContext $context): Response
    {
        $this->authorize('groups.view');

        return Inertia::render('Backoffice/GroupsHistorique/Index', [
            'historiques' => $getGroupsHistorique($request->user()),
            // Hides the redundant Centre column once the context switcher is
            // on a single center (CLAUDE.md §5 centre-filter rule).
            'centerLocked' => ! $context->isAllCenters(),
        ]);
    }
}
