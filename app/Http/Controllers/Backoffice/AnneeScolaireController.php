<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\AnneesScolaires\StoreAnneeScolaireRequest;
use App\Http\Requests\Backoffice\AnneesScolaires\UpdateAnneeScolaireRequest;
use App\Models\AnneeScolaire;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        $this->persist($this->guardCloture($request, $request->validated()));

        return redirect()->route('backoffice.settings', ['tab' => 'annees-scolaires'])
            ->with('success', __('Année scolaire créée.'));
    }

    public function edit(AnneeScolaire $annees_scolaire): RedirectResponse
    {
        return redirect()->route('backoffice.settings');
    }

    public function update(UpdateAnneeScolaireRequest $request, AnneeScolaire $annees_scolaire): RedirectResponse
    {
        $this->persist($this->guardCloture($request, $request->validated(), $annees_scolaire), $annees_scolaire);

        return redirect()->route('backoffice.settings', ['tab' => 'annees-scolaires'])
            ->with('success', __('Année scolaire mise à jour.'));
    }

    /**
     * Make this year the application default (used as the initial active
     * year of the context switcher). Same single-transaction swap as
     * persist(): the previous default is cleared in the same statement.
     */
    public function setDefault(AnneeScolaire $annees_scolaire): RedirectResponse
    {
        $this->authorize('update', $annees_scolaire);

        // Mirror of guardCloture()'s rule, from the other side: the default
        // year is the one every new session opens on, so a CLOSED year must
        // never become it — that would drop everyone into a context which
        // accepts no input.
        if ($annees_scolaire->estCloturee()) {
            return back()->withErrors(['default' => __('A closed academic year cannot be made the default. Reopen it first.')]);
        }

        if (! $annees_scolaire->par_defaut) {
            $this->persist(['par_defaut' => true], $annees_scolaire);
        }

        return redirect()->route('backoffice.settings', ['tab' => 'annees-scolaires'])
            ->with('success', __(':name is now the default academic year.', ['name' => $annees_scolaire->nom]));
    }

    /**
     * Guarded like the Livewire tab: an academic year still referenced by
     * groups/inscriptions cannot be removed. `delete` field error (not a
     * flash) so the React confirm-dialog stays open and shows it inline.
     */
    public function destroy(AnneeScolaire $annees_scolaire): RedirectResponse
    {
        $annees_scolaire->loadCount(['groups', 'inscriptions']);

        if ($annees_scolaire->groups_count || $annees_scolaire->inscriptions_count
            || DB::table('seances')->where('annee_scolaire_id', $annees_scolaire->id)->exists()
            || DB::table('import_batches')->where('annee_scolaire_id', $annees_scolaire->id)->exists()) {
            return back()->withErrors(['delete' => __('This academic year is still in use and cannot be deleted.')]);
        }

        // The default year is what every new session opens on (CRUD-F11).
        if ($annees_scolaire->par_defaut) {
            return back()->withErrors(['delete' => __('The default academic year cannot be deleted. Choose another default first.')]);
        }

        $annees_scolaire->delete();

        return redirect()->route('backoffice.settings', ['tab' => 'annees-scolaires'])
            ->with('success', __('Année scolaire supprimée.'));
    }

    /**
     * ⚠ « Année clôturée » is a WRITE LOCK, not a label (02/09/2026): a
     * ticked year refuses every creation and modification across the whole
     * app, super-admin included (AssertsContextScope). Two guards therefore
     * sit on the switch itself:
     *
     *  1. only a super-admin may tick or untick it. Un-ticking is the ONLY
     *     way to write in a closed year again, so it is the real override —
     *     if any role could flip it, the lock would be advisory;
     *  2. the DEFAULT year can never be closed. It is the year every new
     *     session opens on (CurrentContext falls back to it), so closing it
     *     would drop every user into a context that accepts no input, with
     *     no obvious way out. Make another year the default first.
     *
     * The change is audited like every other column (AnneeScolaire uses
     * Auditable), so who reopened a year, and when, is on the record.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function guardCloture(Request $request, array $data, ?AnneeScolaire $annee = null): array
    {
        if (! array_key_exists('cloturee', $data)) {
            return $data;
        }

        $demandee = (bool) $data['cloturee'];

        if ($demandee === (bool) ($annee->cloturee ?? false)) {
            return $data;
        }

        if (! $request->user()?->hasRole('super-admin')) {
            throw ValidationException::withMessages([
                'cloturee' => __('Only a super-admin can close or reopen an academic year.'),
            ]);
        }

        $deviendraDefaut = (bool) ($data['par_defaut'] ?? $annee->par_defaut ?? false);

        if ($demandee && $deviendraDefaut) {
            throw ValidationException::withMessages([
                'cloturee' => __('The default academic year cannot be closed. Make another year the default first.'),
            ]);
        }

        return $data;
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
