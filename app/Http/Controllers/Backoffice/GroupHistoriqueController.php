<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\GroupHistorique;
use Illuminate\Contracts\View\View;

/**
 * Read-only archive (schema §7): rows are created exclusively by
 * Group::archiverCommeTermine() — no create/update/delete here, ever.
 */
final class GroupHistoriqueController extends Controller
{
    public function index(): View
    {
        $this->authorize('groups.view');

        return view('backoffice.groups-historique.index', [
            'historiques' => GroupHistorique::query()
                ->with(['group', 'enseignant', 'etablissement', 'anneeScolaire', 'archivedBy'])
                ->orderByDesc('archived_at')
                ->paginate(15),
        ]);
    }
}
