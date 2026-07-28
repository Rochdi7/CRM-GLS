<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Expenses\Actions\EnregistrerDepense;
use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Depenses\StoreDepenseRequest;
use App\Http\Requests\Backoffice\Depenses\UpdateDepenseRequest;
use App\Models\Depense;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * ⚠ No destroy(): a recorded expense is never deleted (audit trail);
 * corrections need a compensating entry.
 */
final class DepenseController extends Controller
{
    use ResolvesActingEmployee;

    public function __construct()
    {
        $this->authorizeResource(Depense::class, 'depense');
    }

    public function index(): View
    {
        return view('backoffice.depenses.index', [
            'depenses' => Depense::query()
                ->with(['typeDepense', 'caisse', 'agent'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('backoffice.depenses.create');
    }

    public function store(StoreDepenseRequest $request, EnregistrerDepense $action): RedirectResponse
    {
        // Domain action: creates the expense and decrements caisses.solde
        // in ONE transaction.
        $action->handle($request->validated(), $this->actingEmployee($request));

        return redirect()->route('backoffice.depenses.index')
            ->with('status', __('Dépense enregistrée.'));
    }

    public function show(Depense $depense): View
    {
        return view('backoffice.depenses.show', [
            'depense' => $depense->load(['typeDepense', 'caisse', 'agent']),
        ]);
    }

    public function edit(Depense $depense): View
    {
        return view('backoffice.depenses.edit', ['depense' => $depense]);
    }

    public function update(UpdateDepenseRequest $request, Depense $depense): RedirectResponse
    {
        // montant / caisse_id are not editable (see UpdateDepenseRequest).
        $depense->update($request->validated());

        return redirect()->route('backoffice.depenses.index')
            ->with('status', __('Dépense mise à jour.'));
    }
}
