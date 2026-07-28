<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Group detail page (read-only) + the "Fin de formation" archive action.
 * The list + add/edit CRUD (with fee assignment) is the Livewire GroupsIndex
 * component. Groups are NEVER deleted (schema §6).
 */
final class GroupController extends Controller
{
    public function show(Group $group): View
    {
        $this->authorize('view', $group);

        return view('backoffice.groups.show', [
            'group' => $group->load(['enseignant', 'salle', 'etablissement', 'anneeScolaire', 'frais', 'inscriptions.student', 'historique']),
        ]);
    }

    /**
     * Transition to "Fin de formation" — writes the groups_historique
     * snapshot in the same transaction (Group::archiverCommeTermine).
     */
    public function archive(Request $request, Group $group): RedirectResponse
    {
        $this->authorize('archive', $group);

        if ($group->statut === Group::STATUT_FIN_FORMATION) {
            return back();
        }

        $group->archiverCommeTermine($request->user()?->employee);

        return redirect()->route('backoffice.groups.show', $group)
            ->with('status', __('Group archived (Fin de formation).'));
    }
}
