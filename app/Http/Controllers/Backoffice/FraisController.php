<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Domain\Settings\Queries\GetAccessibleCenterOptions;
use App\Http\Requests\Backoffice\Frais\StoreFraisRequest;
use App\Http\Requests\Backoffice\Frais\UpdateFraisRequest;
use App\Models\Frais;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
    public function __construct(
        private readonly GetAccessibleCenterOptions $accessibleCenters,
    ) {
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
        DB::transaction(function () use ($request): void {
            $data = $request->validated();
            $centres = $data['centres'] ?? [];
            unset($data['centres']);

            $frais = Frais::create($data);
            $frais->etablissements()->sync($this->syncPayload($request, $centres, $frais));
        });

        return redirect()->route('backoffice.settings', ['tab' => 'frais'])
            ->with('success', __('Frais créé.'));
    }

    public function edit(Frais $frai): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function update(UpdateFraisRequest $request, Frais $frai): RedirectResponse
    {
        DB::transaction(function () use ($request, $frai): void {
            $data = $request->validated();
            $centres = $data['centres'] ?? [];
            unset($data['centres']);

            $frai->update($data);
            $frai->etablissements()->sync($this->syncPayload($request, $centres, $frai));
        });

        return redirect()->route('backoffice.settings', ['tab' => 'frais'])
            ->with('success', __('Frais mis à jour.'));
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

        // The pivot cascades on delete, but drop it explicitly so the
        // removal is one intentional statement rather than a side effect.
        $frai->etablissements()->detach();
        $frai->delete();

        return redirect()->route('backoffice.settings', ['tab' => 'frais'])
            ->with('success', __('Frais supprimé.'));
    }

    /**
     * Turn the submitted center lines into a sync payload keyed by
     * etablissement_id, so each attached center carries its own amount.
     *
     * Two guards, both about not trusting the client:
     *  - a center the acting user may not act on is dropped from the
     *    payload, mirroring the picker they were served (the Form Request
     *    only checks the id exists, not that it is theirs);
     *  - prices for centers OUTSIDE that user's scope are carried over
     *    unchanged, so a center-limited admin editing a national fee
     *    cannot silently unprice the branches they never saw.
     *
     * @param  array<int, array{etablissement_id: int|string, montant: mixed}>  $centres
     * @return array<int, array{montant: float}>
     */
    private function syncPayload(Request $request, array $centres, Frais $frais): array
    {
        $allowed = $this->accessibleCenters->allowedIds($request->user());

        // Start from what other-scope centers already pay — sync() replaces
        // the whole set, so anything missing here would be detached.
        $payload = [];
        foreach ($frais->etablissements()->get() as $etablissement) {
            if (! in_array($etablissement->id, $allowed, true)) {
                $payload[$etablissement->id] = ['montant' => (float) $etablissement->pivot->montant];
            }
        }

        foreach ($centres as $ligne) {
            $id = (int) ($ligne['etablissement_id'] ?? 0);

            if ($id === 0 || ! in_array($id, $allowed, true)) {
                continue;
            }

            $payload[$id] = ['montant' => (float) ($ligne['montant'] ?? 0)];
        }

        return $payload;
    }
}
