<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Groups\Queries\GetGroupDetails;
use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Group detail page (read-only) + the "Fin de formation" archive action.
 * The list + add/edit CRUD (with fee assignment) is the Livewire GroupsIndex
 * component. Groups are NEVER deleted (schema §6).
 *
 * archive() is UNCHANGED by the Inertia migration (Phase 5 scope: read-only
 * pages only) — it still redirects to this same show route with a Blade
 * flash message; the show page renders it as a plain HTML form, not a
 * React mutation.
 */
final class GroupController extends Controller
{
    public function show(Request $request, Group $group, GetGroupDetails $getGroupDetails): Response
    {
        $this->authorize('view', $group);

        return Inertia::render('Backoffice/Groups/Show', [
            'group' => $getGroupDetails($group, $request->user()),
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
