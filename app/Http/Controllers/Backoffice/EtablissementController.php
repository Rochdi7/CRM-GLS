<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Etablissements\StoreEtablissementRequest;
use App\Http\Requests\Backoffice\Etablissements\UpdateEtablissementRequest;
use App\Models\Etablissement;
use Illuminate\Http\RedirectResponse;

/**
 * Établissements (centers) CRUD — Inertia/React (Phase 6), same UI slot as
 * before (Settings → Établissements tab, ?tab=etablissements). index/create/
 * show/edit still redirect to Settings (no page of their own — the tab
 * component IS the UI); store/update/destroy are the real mutation
 * endpoints the React form/delete-dialog submit to.
 */
final class EtablissementController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Etablissement::class, 'etablissement');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function store(StoreEtablissementRequest $request): RedirectResponse
    {
        Etablissement::create($request->validated());

        return redirect()->route('backoffice.settings', ['tab' => 'etablissements'])
            ->with('status', __('Établissement créé.'));
    }

    public function show(Etablissement $etablissement): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function edit(Etablissement $etablissement): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function update(UpdateEtablissementRequest $request, Etablissement $etablissement): RedirectResponse
    {
        $etablissement->update($request->validated());

        return redirect()->route('backoffice.settings', ['tab' => 'etablissements'])
            ->with('status', __('Établissement mis à jour.'));
    }

    /**
     * Guarded in the same way as the Livewire tab: a center still holding
     * rooms/staff/students cannot be removed. Checked explicitly (not left
     * to the DB restrict FK) so the user gets the same safe French message
     * instead of a raw constraint-violation error. Returned as a `delete`
     * field error (back()->withErrors) rather than a flash message so the
     * React confirm-dialog can keep itself open and show it inline — same
     * UX as the Livewire tab's $this->addError('delete', ...).
     */
    public function destroy(Etablissement $etablissement): RedirectResponse
    {
        $etablissement->loadCount(['salles', 'employees', 'students']);

        if ($etablissement->salles_count || $etablissement->employees_count || $etablissement->students_count) {
            return back()->withErrors(['delete' => __('This center is still in use and cannot be deleted.')]);
        }

        $etablissement->delete();

        return redirect()->route('backoffice.settings', ['tab' => 'etablissements'])
            ->with('status', __('Établissement supprimé.'));
    }
}
