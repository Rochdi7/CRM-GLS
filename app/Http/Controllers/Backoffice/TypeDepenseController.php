<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Expenses\Queries\GetTypesDepensesList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\TypesDepenses\StoreTypeDepenseRequest;
use App\Http\Requests\Backoffice\TypesDepenses\UpdateTypeDepenseRequest;
use App\Models\TypeDepense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Types de dépenses (expense types) CRUD — Inertia/React (Phase 6). Was
 * previously the 3rd tab of the Livewire Gestion des dépenses page; given
 * its own standalone page here since Depenses/Remboursements (the other two
 * tabs) are out of Phase 6 scope (docs/phase-6-simple-crud-inventory.md
 * §Q2) — mixing an Inertia tab with two Livewire tabs on one page isn't
 * viable. `backoffice.depenses.index?tab=types` used to render this
 * inline; the tab itself is now removed there (2-tab page) and any old
 * deep-link redirects here instead.
 *
 * is_system rows are LOCKED (schema §12: payroll/transfer code depends on
 * their exact names) — guarded by an explicit, unconditional check BEFORE
 * the policy call so it also stops super-admin (Gate::before otherwise
 * bypasses every policy), exactly mirroring the retired Livewire
 * TypesDepensesIndex::guardSystemType().
 */
final class TypeDepenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(TypeDepense::class, 'types_depense');
    }

    public function index(Request $request, GetTypesDepensesList $getTypesDepensesList): Response
    {
        $search = (string) $request->query('search', '');
        $perPage = (int) $request->query('per_page', 10);

        return Inertia::render('Backoffice/TypesDepenses/Index', [
            'types' => $getTypesDepensesList($search, in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10),
            'filters' => ['search' => $search],
            'permissions' => [
                'create' => $request->user()->can('create', TypeDepense::class),
                'update' => $request->user()->can('expense-types.update'),
                'delete' => $request->user()->can('expense-types.delete'),
            ],
        ]);
    }

    public function store(StoreTypeDepenseRequest $request): RedirectResponse
    {
        // The admin form only ever creates custom types (schema §12).
        TypeDepense::create([
            ...$request->validated(),
            'is_system' => false,
        ]);

        return redirect()->route('backoffice.types-depenses.index')
            ->with('success', __('Type de dépense créé.'));
    }

    public function update(UpdateTypeDepenseRequest $request, TypeDepense $types_depense): RedirectResponse
    {
        // System types are protected — payroll/transfer code depends on them.
        // Checked unconditionally, BEFORE authorize(), so even a super-admin
        // (who bypasses every policy via Gate::before) cannot edit one.
        abort_if($types_depense->is_system, 403, __('Les types système ne sont pas modifiables.'));
        $this->authorize('update', $types_depense);

        $types_depense->update($request->validated());

        return redirect()->route('backoffice.types-depenses.index')
            ->with('success', __('Type de dépense mis à jour.'));
    }

    public function destroy(TypeDepense $types_depense): RedirectResponse
    {
        abort_if($types_depense->is_system, 403, __('Les types système ne sont pas supprimables.'));
        $this->authorize('delete', $types_depense);

        $types_depense->loadCount('depenses');

        if ($types_depense->depenses_count) {
            return back()->withErrors(['delete' => __('This expense type is used by expenses and cannot be deleted.')]);
        }

        $types_depense->delete();

        return redirect()->route('backoffice.types-depenses.index')
            ->with('success', __('Type de dépense supprimé.'));
    }
}
