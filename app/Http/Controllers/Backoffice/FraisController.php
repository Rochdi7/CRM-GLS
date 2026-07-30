<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Frais\StoreFraisRequest;
use App\Http\Requests\Backoffice\Frais\UpdateFraisRequest;
use App\Models\Frais;
use Illuminate\Http\RedirectResponse;

/**
 * Frais (fee catalog) CRUD — new in Phase 6 (docs/phase-6-simple-crud-
 * inventory.md §Q1: no controller/routes/Form Requests existed before this
 * phase, only the Livewire FraisTab). index/create/edit redirect to
 * Settings (Settings → Frais tab, ?tab=frais is the only UI — mirrors the
 * other three referential modules); store/update/destroy are the real
 * mutation endpoints. Catalog CRUD only — never touches group_frais
 * amounts/due-dates or inscription_fees math (Groups/Registrations modules,
 * out of Phase 6 scope).
 */
final class FraisController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Frais::class, 'frai');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function store(StoreFraisRequest $request): RedirectResponse
    {
        Frais::create($request->validated());

        return redirect()->route('backoffice.settings', ['tab' => 'frais'])
            ->with('status', __('Frais créé.'));
    }

    public function edit(Frais $frai): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function update(UpdateFraisRequest $request, Frais $frai): RedirectResponse
    {
        $frai->update($request->validated());

        return redirect()->route('backoffice.settings', ['tab' => 'frais'])
            ->with('status', __('Frais mis à jour.'));
    }

    /**
     * Guarded like the Livewire tab: a fee still assigned to groups cannot
     * be removed. `delete` field error (not a flash) so the React
     * confirm-dialog stays open and shows it inline.
     */
    public function destroy(Frais $frai): RedirectResponse
    {
        $frai->loadCount('groups');

        if ($frai->groups_count) {
            return back()->withErrors(['delete' => __('This fee is assigned to groups and cannot be deleted.')]);
        }

        $frai->delete();

        return redirect()->route('backoffice.settings', ['tab' => 'frais'])
            ->with('status', __('Frais supprimé.'));
    }
}
