<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Salles\StoreSalleRequest;
use App\Http\Requests\Backoffice\Salles\UpdateSalleRequest;
use App\Models\Salle;
use Illuminate\Http\RedirectResponse;

final class SalleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Salle::class, 'salle');
    }

    /**
     * The Paramètres page (Settings → Salles tab) is the primary UI for this
     * referential data — these listing/form pages have no view of their own,
     * so they redirect there while staying permission-protected.
     */
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

        return redirect()->route('backoffice.salles.index')
            ->with('status', __('Salle créée.'));
    }

    public function edit(Salle $salle): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function update(UpdateSalleRequest $request, Salle $salle): RedirectResponse
    {
        $salle->update($request->validated());

        return redirect()->route('backoffice.salles.index')
            ->with('status', __('Salle mise à jour.'));
    }

    public function destroy(Salle $salle): RedirectResponse
    {
        $salle->delete();

        return redirect()->route('backoffice.salles.index')
            ->with('status', __('Salle supprimée.'));
    }
}
