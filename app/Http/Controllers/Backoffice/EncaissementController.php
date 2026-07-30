<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Payments\Actions\EnregistrerEncaissement;
use App\Domain\Payments\Queries\GetEncaissementDetails;
use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Encaissements\StoreEncaissementRequest;
use App\Http\Requests\Backoffice\Encaissements\UpdateEncaissementRequest;
use App\Models\Encaissement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * ⚠ No destroy(): a recorded payment is never deleted — corrections go
 * through a remboursement + new encaissement so the money trail stays intact.
 */
final class EncaissementController extends Controller
{
    use ResolvesActingEmployee;

    public function __construct()
    {
        $this->authorizeResource(Encaissement::class, 'encaissement');
    }

    public function index(): View
    {
        return view('backoffice.encaissements.index', [
            'encaissements' => Encaissement::query()
                ->with(['student', 'fee.inscription', 'caisse', 'agent'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('backoffice.encaissements.create');
    }

    public function store(StoreEncaissementRequest $request, EnregistrerEncaissement $action): RedirectResponse
    {
        // Domain action: creates the payment, increments caisses.solde and
        // recomputes the fee statut in ONE transaction.
        $action->handle($request->validated(), $this->actingEmployee($request));

        return redirect()->route('backoffice.encaissements.index')
            ->with('status', __('Paiement enregistré.'));
    }

    public function show(Encaissement $encaissement, GetEncaissementDetails $getEncaissementDetails): Response
    {
        return Inertia::render('Backoffice/Encaissements/Show', [
            'encaissement' => $getEncaissementDetails($encaissement),
        ]);
    }

    public function edit(Encaissement $encaissement): View
    {
        return view('backoffice.encaissements.edit', ['encaissement' => $encaissement]);
    }

    public function update(UpdateEncaissementRequest $request, Encaissement $encaissement): RedirectResponse
    {
        // montant / caisse_id are not editable (see UpdateEncaissementRequest);
        // this edit is audit-logged by LogsActivity.
        $encaissement->update($request->validated());

        return redirect()->route('backoffice.encaissements.index')
            ->with('status', __('Paiement mis à jour.'));
    }
}
