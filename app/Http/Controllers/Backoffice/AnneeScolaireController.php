<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\AnneesScolaires\StoreAnneeScolaireRequest;
use App\Http\Requests\Backoffice\AnneesScolaires\UpdateAnneeScolaireRequest;
use App\Models\AnneeScolaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * Années scolaires (academic years) CRUD — Inertia/React (Phase 6), same UI
 * slot as before (Settings → Années scolaires tab, ?tab=annees-scolaires).
 */
final class AnneeScolaireController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AnneeScolaire::class, 'annees_scolaire');
    }

    public function index(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function store(StoreAnneeScolaireRequest $request): RedirectResponse
    {
        $this->persist($request->validated());

        return redirect()->route('backoffice.settings', ['tab' => 'annees-scolaires'])
            ->with('status', __('Année scolaire créée.'));
    }

    public function edit(AnneeScolaire $annees_scolaire): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function update(UpdateAnneeScolaireRequest $request, AnneeScolaire $annees_scolaire): RedirectResponse
    {
        $this->persist($request->validated(), $annees_scolaire);

        return redirect()->route('backoffice.settings', ['tab' => 'annees-scolaires'])
            ->with('status', __('Année scolaire mise à jour.'));
    }

    /**
     * Guarded like the Livewire tab: an academic year still referenced by
     * groups/inscriptions cannot be removed. `delete` field error (not a
     * flash) so the React confirm-dialog stays open and shows it inline.
     */
    public function destroy(AnneeScolaire $annees_scolaire): RedirectResponse
    {
        $annees_scolaire->loadCount(['groups', 'inscriptions']);

        if ($annees_scolaire->groups_count || $annees_scolaire->inscriptions_count) {
            return back()->withErrors(['delete' => __('This academic year is still in use and cannot be deleted.')]);
        }

        $annees_scolaire->delete();

        return redirect()->route('backoffice.settings', ['tab' => 'annees-scolaires'])
            ->with('status', __('Année scolaire supprimée.'));
    }

    /**
     * Only one year can be par_defaut at a time.
     *
     * @param  array<string, mixed>  $data
     */
    private function persist(array $data, ?AnneeScolaire $annee = null): void
    {
        DB::transaction(function () use ($data, $annee): void {
            if (($data['par_defaut'] ?? false)) {
                AnneeScolaire::query()->where('par_defaut', true)->update(['par_defaut' => false]);
            }

            $annee === null
                ? AnneeScolaire::create($data)
                : $annee->update($data);
        });
    }
}
