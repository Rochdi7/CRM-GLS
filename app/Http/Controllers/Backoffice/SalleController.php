<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Salles\StoreSalleRequest;
use App\Http\Requests\Backoffice\Salles\UpdateSalleRequest;
use App\Models\Salle;
use Illuminate\Http\RedirectResponse;

/**
 * Salles (rooms) CRUD — Inertia/React (Phase 6), same UI slot as before
 * (Settings → Salles tab, ?tab=salles). See StoreSalleRequest/
 * UpdateSalleRequest for the center-access validation fix (§Q3).
 */
final class SalleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Salle::class, 'salle');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function store(StoreSalleRequest $request): RedirectResponse
    {
        Salle::create($request->validated());

        return redirect()->route('backoffice.settings', ['tab' => 'salles'])
            ->with('success', __('Salle créée.'));
    }

    public function edit(Salle $salle): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function update(UpdateSalleRequest $request, Salle $salle): RedirectResponse
    {
        $salle->update($request->validated());

        return redirect()->route('backoffice.settings', ['tab' => 'salles'])
            ->with('success', __('Salle mise à jour.'));
    }

    /**
     * Guarded like the Livewire tab: a room still assigned to groups cannot
     * be removed. `delete` field error (not a flash) so the React
     * confirm-dialog stays open and shows it inline.
     */
    public function destroy(Salle $salle): RedirectResponse
    {
        $salle->loadCount('groups');

        if ($salle->groups_count) {
            return back()->withErrors(['delete' => __('This room is still assigned to groups and cannot be deleted.')]);
        }

        $salle->delete();

        return redirect()->route('backoffice.settings', ['tab' => 'salles'])
            ->with('success', __('Salle supprimée.'));
    }
}
