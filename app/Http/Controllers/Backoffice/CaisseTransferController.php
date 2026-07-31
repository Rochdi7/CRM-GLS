<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Finance\Queries\GetCaisseTransferDetails;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CaisseTransfers\StoreCaisseTransferRequest;
use App\Http\Requests\Backoffice\CaisseTransfers\UpdateCaisseTransferRequest;
use App\Models\CaisseTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Two-step request/validate flow (Phase 10,
 * docs/phase-10-finance-audit.md §2.3 / §5): store() = request (balances
 * untouched) · validateAction() = approval by a DIFFERENT employee (balances
 * move, one transaction, self-validation refused). No destroy() — full
 * audit trail. Mirrors CaisseTransfersIndex one-for-one, including its
 * soft-error UX for "no employee record" and "not pending" guards (a
 * deliberate divergence from this controller's OLD hard-abort behavior via
 * ResolvesActingEmployee — see audit finding #2: the Livewire component has
 * always used a soft form error here, never a 403). The list itself is
 * served by CaisseController@index (Transfers shares its tabbed page with
 * Caisses, matching the former Livewire-tab Blade shell exactly) — this
 * controller only handles the mutations + the detail page.
 *
 * docs/phase-10-finance-mapping.md Q3: the validate() action's
 * "TODO(permissions phase): gate to Directeur-level roles" is carried
 * forward unaddressed, exactly matching current Livewire behavior — ships
 * gated by cash-transfers.validate only.
 */
final class CaisseTransferController extends Controller
{
    public function store(StoreCaisseTransferRequest $request, DemanderTransfertCaisse $action): RedirectResponse
    {
        $this->authorize('create', CaisseTransfer::class);

        $requester = $request->user()->employee;

        if ($requester === null) {
            throw ValidationException::withMessages([
                'caisse_source_id' => __('Your account is not linked to any employee record.'),
            ]);
        }

        $action->handle($request->validated(), $requester);

        return redirect()->route('backoffice.caisses.index', ['tab' => 'transferts'])
            ->with('success', __('Transfer requested — awaiting validation.'));
    }

    public function show(CaisseTransfer $caisse_transfer, GetCaisseTransferDetails $getCaisseTransferDetails): Response
    {
        $this->authorize('view', $caisse_transfer);

        return Inertia::render('Backoffice/CaisseTransfers/Show', [
            'transfer' => $getCaisseTransferDetails($caisse_transfer),
        ]);
    }

    public function update(UpdateCaisseTransferRequest $request, CaisseTransfer $caisse_transfer): RedirectResponse
    {
        $this->authorize('update', $caisse_transfer);

        // A validated (or cancelled) transfer is frozen — matches
        // CaisseTransfersIndex::save()'s soft error, not a hard 403.
        if ($caisse_transfer->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
            throw ValidationException::withMessages([
                'note' => __('Only a pending transfer can be edited.'),
            ]);
        }

        $data = $request->validated();

        // Only note (and an explicit cancellation via statut=Annulé) are
        // ever written here — tills/amount are structurally absent from
        // UpdateCaisseTransferRequest's rules, matching the Livewire form's
        // own read-only tills/amount in edit mode.
        $caisse_transfer->update([
            'note' => $data['note'] ?? null,
            ...(isset($data['statut']) ? ['statut' => $data['statut']] : []),
        ]);

        return redirect()->route('backoffice.caisses.index', ['tab' => 'transferts'])
            ->with('success', __('Transfer updated.'));
    }

    /**
     * Approval step — moves real money (PUT /…/{transfer}/validate).
     */
    public function validateAction(Request $request, CaisseTransfer $caisse_transfer, ValiderTransfertCaisse $action): RedirectResponse
    {
        $this->authorize('validate', $caisse_transfer);

        $validator = $request->user()->employee;

        if ($validator === null) {
            throw ValidationException::withMessages([
                'validate' => __('Your account is not linked to any employee record.'),
            ]);
        }

        try {
            $action->handle($caisse_transfer, $validator);
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? __('This transfer cannot be validated.');

            throw ValidationException::withMessages(['validate' => $message]);
        }

        return redirect()->route('backoffice.caisses.index', ['tab' => 'transferts'])
            ->with('success', __('Transfer validated — balances have been updated.'));
    }
}
