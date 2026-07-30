<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Groups\Queries\GetGroupsHistorique;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only archive (schema §7): rows are created exclusively by
 * Group::archiverCommeTermine() — no create/update/delete here, ever.
 */
final class GroupHistoriqueController extends Controller
{
    public function index(GetGroupsHistorique $getGroupsHistorique): Response
    {
        $this->authorize('groups.view');

        return Inertia::render('Backoffice/GroupsHistorique/Index', [
            'historiques' => $getGroupsHistorique(),
        ]);
    }
}
