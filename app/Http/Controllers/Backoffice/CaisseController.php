<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Queries\GetCaisseDetails;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Caisses\StoreCaisseRequest;
use App\Http\Requests\Backoffice\Caisses\UpdateCaisseRequest;
use App\Models\Caisse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Phase 5 (docs/phase-5-read-pages-inventory.md): only show() is reachable
 * (create/store/edit/update/destroy have no registered routes — the real
 * Caisse CRUD is the Livewire tabbed "Gestion de la caisse" page, per
 * CLAUDE.md §11). Those methods are left completely unchanged; only
 * show()'s return type/view was converted.
 */
final class CaisseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Caisse::class, 'caisse');
    }

    public function index(): View
    {
        // Per-caisse balance dashboard (structure doc §4).
        return view('backoffice.caisses.index', [
            'caisses' => Caisse::query()->with(['etablissement', 'responsable'])->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('backoffice.caisses.create');
    }

    public function store(StoreCaisseRequest $request): RedirectResponse
    {
        Caisse::create($request->validated());

        return redirect()->route('backoffice.caisses.index')
            ->with('status', __('Caisse créée.'));
    }

    public function show(Caisse $caisse, GetCaisseDetails $getCaisseDetails): Response
    {
        return Inertia::render('Backoffice/Caisses/Show', [
            'caisse' => $getCaisseDetails($caisse),
        ]);
    }

    public function edit(Caisse $caisse): View
    {
        return view('backoffice.caisses.edit', ['caisse' => $caisse]);
    }

    public function update(UpdateCaisseRequest $request, Caisse $caisse): RedirectResponse
    {
        // UpdateCaisseRequest deliberately excludes `solde` (schema §10).
        $caisse->update($request->validated());

        return redirect()->route('backoffice.caisses.index')
            ->with('status', __('Caisse mise à jour.'));
    }

    public function destroy(Caisse $caisse): RedirectResponse
    {
        // Restrict FKs block deleting a till with financial history.
        $caisse->delete();

        return redirect()->route('backoffice.caisses.index')
            ->with('status', __('Caisse supprimée.'));
    }
}
