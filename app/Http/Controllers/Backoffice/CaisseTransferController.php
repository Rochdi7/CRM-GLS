<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Actions\DemanderTransfertCaisse;
use App\Domain\Finance\Actions\ValiderTransfertCaisse;
use App\Domain\Finance\Support\CaisseResolver;
use App\Domain\Finance\Queries\GetCaisseTransferDetails;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\CaisseTransfers\StoreCaisseTransferRequest;
use App\Http\Requests\Backoffice\CaisseTransfers\UpdateCaisseTransferRequest;
use App\Models\CaisseTransfer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    use AssertsContextScope;

    public function store(StoreCaisseTransferRequest $request, DemanderTransfertCaisse $action): RedirectResponse
    {
        $this->authorize('create', CaisseTransfer::class);

        // A transfer carries no annee_scolaire_id (it moves cash, §11), so
        // the closed-year lock is asserted on the ACTIVE year.
        $this->assertContextAnneeOuverte('caisse_destination_id');

        $requester = $request->user()->employee;

        if ($requester === null) {
            throw ValidationException::withMessages([
                'caisse_source_id' => __('Your account is not linked to any employee record.'),
            ]);
        }

        // The source is ALWAYS the requester's own PHYSICAL till — never
        // chosen client-side, for any role including super-admin (the modal
        // shows no source picker). Same server-derived-till rule + self-heal
        // as EncaissementController::store() (CaisseResolver::tillOf — type
        // Caissière only, never an Externe safe they happen to be
        // responsable of).
        $source = app(CaisseResolver::class)->tillOf($requester);

        $data = $request->validated();

        if ((int) $data['caisse_destination_id'] === $source->id) {
            throw ValidationException::withMessages([
                'caisse_destination_id' => __('The destination till must be different from your own till.'),
            ]);
        }

        $action->handle([...$data, 'caisse_source_id' => $source->id], $requester);

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
        $this->assertContextAnneeOuverte('statut');

        $data = $request->validated();

        // Cancelling is the requester's decision (or the recipient declining
        // it): a third party holding cash-transfers.update must not be able
        // to void someone else's pending request.
        if (($data['statut'] ?? null) === CaisseTransfer::STATUT_ANNULE) {
            $employee = $request->user()->employee;
            $isParty = $employee !== null && (
                $caisse_transfer->requested_by === $employee->id
                || $employee->caisses()->whereKey($caisse_transfer->caisse_destination_id)->exists()
            );

            if (! $isParty) {
                throw ValidationException::withMessages([
                    'statut' => __('Only the requester or the recipient can cancel a transfer.'),
                ]);
            }
        }

        DB::transaction(function () use ($caisse_transfer, $data): void {
            // Re-read under lock so a cancel can't interleave with a
            // validation (ValiderTransfertCaisse locks the same row).
            $locked = CaisseTransfer::query()->whereKey($caisse_transfer->getKey())->lockForUpdate()->firstOrFail();

            // A validated (or cancelled) transfer is frozen — matches
            // CaisseTransfersIndex::save()'s soft error, not a hard 403.
            if ($locked->statut !== CaisseTransfer::STATUT_EN_ATTENTE) {
                throw ValidationException::withMessages([
                    'note' => __('Only a pending transfer can be edited.'),
                ]);
            }

            // Only note (and an explicit cancellation via statut=Annulé) are
            // ever written here — tills/amount are structurally absent from
            // UpdateCaisseTransferRequest's rules.
            $locked->update([
                'note' => $data['note'] ?? null,
                ...(isset($data['statut']) ? ['statut' => $data['statut']] : []),
            ]);
        });

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
