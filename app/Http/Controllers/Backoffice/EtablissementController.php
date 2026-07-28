<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Etablissements\StoreEtablissementRequest;
use App\Http\Requests\Backoffice\Etablissements\UpdateEtablissementRequest;
use App\Models\Etablissement;
use Illuminate\Http\RedirectResponse;

final class EtablissementController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Etablissement::class, 'etablissement');
    }

    /**
     * The Paramètres page (Settings → Établissements tab) is the primary UI
     * for this referential data — these listing/form pages have no view of
     * their own, so they redirect there while staying permission-protected.
     */
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

        return redirect()->route('backoffice.etablissements.index')
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

        return redirect()->route('backoffice.etablissements.index')
            ->with('status', __('Établissement mis à jour.'));
    }

    public function destroy(Etablissement $etablissement): RedirectResponse
    {
        // DB-level restrict FKs (salles…) block deletion of a branch in use.
        $etablissement->delete();

        return redirect()->route('backoffice.etablissements.index')
            ->with('status', __('Établissement supprimé.'));
    }
}
