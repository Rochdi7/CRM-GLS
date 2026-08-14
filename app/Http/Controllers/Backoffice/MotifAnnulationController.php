<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\MotifsAnnulation\StoreMotifAnnulationRequest;
use App\Http\Requests\Backoffice\MotifsAnnulation\UpdateMotifAnnulationRequest;
use App\Models\MotifAnnulation;
use App\Models\Seance;
use Illuminate\Http\RedirectResponse;

/**
 * Raisons d'annulation ou archivage CRUD — same pattern as BanqueController:
 * catalog lives as a Paramètres tab (?tab=motifs-annulation), index/create/
 * edit redirect there, store/update/destroy are the real mutation endpoints.
 * Restricted to super-admin by design — the `cancellation-reasons.*`
 * permissions are deliberately absent from every role in
 * PermissionRegistry::matrix(), so only the Gate::before super-admin bypass
 * grants access. is_system rows ("Changement de groupe" — written by the
 * group-change flow) are LOCKED: the abort_if guards below are the real lock
 * for super-admins, who bypass MotifAnnulationPolicy entirely.
 */
final class MotifAnnulationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(MotifAnnulation::class, 'motifAnnulation');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function store(StoreMotifAnnulationRequest $request): RedirectResponse
    {
        MotifAnnulation::create([
            ...$request->validated(),
            'is_system' => false,
        ]);

        return redirect()->route('backoffice.settings', ['tab' => 'motifs-annulation'])
            ->with('success', __('Raison créée.'));
    }

    public function edit(MotifAnnulation $motifAnnulation): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function update(UpdateMotifAnnulationRequest $request, MotifAnnulation $motifAnnulation): RedirectResponse
    {
        abort_if($motifAnnulation->is_system, 403, __('Les raisons système ne sont pas modifiables.'));

        $motifAnnulation->update($request->validated());

        return redirect()->route('backoffice.settings', ['tab' => 'motifs-annulation'])
            ->with('success', __('Raison mise à jour.'));
    }

    /**
     * Guarded like Banque: a reason still referenced by a cancelled séance's
     * free-text `motif_annulation` column cannot be removed. `delete` field
     * error (not a flash) so the React confirm-dialog stays open and shows
     * it inline.
     */
    public function destroy(MotifAnnulation $motifAnnulation): RedirectResponse
    {
        abort_if($motifAnnulation->is_system, 403, __('Les raisons système ne sont pas supprimables.'));

        if (Seance::where('motif_annulation', $motifAnnulation->nom)->exists()) {
            return back()->withErrors(['delete' => __('Cette raison est utilisée par des séances annulées et ne peut pas être supprimée.')]);
        }

        $motifAnnulation->delete();

        return redirect()->route('backoffice.settings', ['tab' => 'motifs-annulation'])
            ->with('success', __('Raison supprimée.'));
    }
}
