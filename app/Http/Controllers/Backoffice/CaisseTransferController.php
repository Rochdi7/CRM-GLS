<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Finance\Queries\GetCaisseTransferDetails;
use App\Http\Controllers\Backoffice\Concerns\ResolvesActingEmployee;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CaisseTransfers\StoreCaisseTransferRequest;
use App\Http\Requests\Backoffice\CaisseTransfers\UpdateCaisseTransferRequest;
use App\Models\CaisseTransfer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Two-step request/validate flow (structure doc §7):
 * store() = request (balances untouched) · validate() = approval by a
 * DIFFERENT employee (balances move). No destroy() — full audit trail.
 *
 * TODO(permissions phase): gate validate() to Directeur-level roles.
 */
final class CaisseTransferController extends Controller
{
    use ResolvesActingEmployee;

    public function __construct()
    {
        $this->authorizeResource(CaisseTransfer::class, 'caisse_transfer');
    }

    public function index(): View
    {
        return view('backoffice.caisse-transfers.index', [
            'transfers' => CaisseTransfer::query()
                ->with(['caisseSource', 'caisseDestination', 'requestedBy', 'validatedBy'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('backoffice.caisse-transfers.create');
    }

    public function store(StoreCaisseTransferRequest $request, DemanderTransfertCaisse $action): RedirectResponse
    {
        $action->handle($request->validated(), $this->actingEmployee($request));

        return redirect()->route('backoffice.caisse-transfers.index')
            ->with('status', __('Transfert demandé — en attente de validation.'));
    }

    public function show(CaisseTransfer $caisse_transfer, GetCaisseTransferDetails $getCaisseTransferDetails): Response
    {
        return Inertia::render('Backoffice/CaisseTransfers/Show', [
            'transfer' => $getCaisseTransferDetails($caisse_transfer),
        ]);
    }

    public function update(UpdateCaisseTransferRequest $request, CaisseTransfer $caisse_transfer): RedirectResponse
    {
        // Only note edits / cancellation, and only while still pending.
        abort_unless($caisse_transfer->statut === CaisseTransfer::STATUT_EN_ATTENTE, 403,
            __('Seul un transfert en attente peut être modifié.'));

        $caisse_transfer->update($request->validated());

        return redirect()->route('backoffice.caisse-transfers.index')
            ->with('status', __('Transfert mis à jour.'));
    }

    /**
     * Approval step — moves real money (POST /…/{transfer}/validate).
     */
    public function validate(Request $request, CaisseTransfer $caisse_transfer, ValiderTransfertCaisse $action): RedirectResponse
    {
        $this->authorize('validate', $caisse_transfer);

        $action->handle($caisse_transfer, $this->actingEmployee($request));

        return redirect()->route('backoffice.caisse-transfers.index')
            ->with('status', __('Transfert validé — les soldes ont été mis à jour.'));
    }
}
