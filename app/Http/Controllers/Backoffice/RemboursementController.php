<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Actions\EnregistrerRemboursement;
use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Remboursements\StoreRemboursementRequest;
use App\Http\Requests\Backoffice\Remboursements\UpdateRemboursementRequest;
use App\Models\Remboursement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * ⚠ No destroy(): a recorded refund is never deleted (audit trail).
 */
final class RemboursementController extends Controller
{
    use ResolvesActingEmployee;

    public function __construct()
    {
        $this->authorizeResource(Remboursement::class, 'remboursement');
    }

    public function index(): View
    {
        return view('backoffice.remboursements.index', [
            'remboursements' => Remboursement::query()
                ->with(['beneficiaire', 'caisse', 'agent'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('backoffice.remboursements.create');
    }

    public function store(StoreRemboursementRequest $request, EnregistrerRemboursement $action): RedirectResponse
    {
        // Domain action: creates the refund and decrements caisses.solde
        // in ONE transaction.
        $action->handle($request->validated(), $this->actingEmployee($request));

        return redirect()->route('backoffice.remboursements.index')
            ->with('status', __('Remboursement enregistré.'));
    }

    public function show(Remboursement $remboursement): View
    {
        return view('backoffice.remboursements.show', [
            'remboursement' => $remboursement->load(['beneficiaire', 'caisse', 'agent']),
        ]);
    }

    public function edit(Remboursement $remboursement): View
    {
        return view('backoffice.remboursements.edit', ['remboursement' => $remboursement]);
    }

    public function update(UpdateRemboursementRequest $request, Remboursement $remboursement): RedirectResponse
    {
        // montant / caisse_id are not editable (see UpdateRemboursementRequest).
        $remboursement->update($request->validated());

        return redirect()->route('backoffice.remboursements.index')
            ->with('status', __('Remboursement mis à jour.'));
    }
}
