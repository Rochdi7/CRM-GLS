<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Actions\EnregistrerRemboursement;
use App\Domain\Finance\Queries\GetStudentPaymentsForRefund;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\Remboursements\StoreRemboursementRequest;
use App\Http\Requests\Backoffice\Remboursements\UpdateRemboursementRequest;
use App\Models\Remboursement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Refund create/edit (Phase 10, docs/phase-10-finance-audit.md §2.6) —
 * mirrors RemboursementsIndex one-for-one. The list itself is served by
 * DepenseController@index (Remboursements shares its tabbed page with
 * Dépenses, matching the former Livewire-tab Blade shell exactly) — this
 * controller only handles the two mutations. No destroy(): a recorded
 * refund is never deleted (audit trail). No show(): Remboursements has zero
 * detail page anywhere in the live app and this phase does not add one
 * (docs/phase-10-finance-mapping.md Q2). No maximum-refund-amount check is
 * added either (Q1) — `min:0.01` remains the only numeric constraint,
 * matching current behavior exactly.
 */
final class RemboursementController extends Controller
{
    /**
     * A student's fee-targeted payments — the create form's "which payment
     * are we refunding?" cascade (GetStudentPaymentsForRefund). Gated the
     * same as creating a refund.
     */
    public function studentPayments(int $student, GetStudentPaymentsForRefund $getStudentPaymentsForRefund): JsonResponse
    {
        $this->authorize('create', Remboursement::class);

        return response()->json(['payments' => $getStudentPaymentsForRefund($student)]);
    }

    public function store(StoreRemboursementRequest $request, EnregistrerRemboursement $action): RedirectResponse
    {
        $this->authorize('create', Remboursement::class);

        $agent = $request->user()->employee;

        if ($agent === null) {
            throw ValidationException::withMessages([
                'beneficiaire_id' => __('Your account is not linked to any employee record.'),
            ]);
        }

        // The till is ALWAYS the acting employee's own caisse — never chosen
        // client-side (the modal shows no caisse field). Same self-heal as
        // EncaissementController::store() for pre-provisioner accounts.
        $caisse = $agent->caisses()->first()
            ?? app(\App\Services\CaisseProvisioner::class)->provisionFor($agent);

        // Domain action: creates the refund AND decrements caisses.solde in
        // ONE transaction.
        $action->handle([
            ...$request->validated(),
            'caisse_id' => $caisse->id,
        ], $agent);

        return redirect()->route('backoffice.depenses.index', ['tab' => 'remboursements'])
            ->with('success', __('Refund recorded.'));
    }

    public function update(UpdateRemboursementRequest $request, Remboursement $remboursement): RedirectResponse
    {
        $this->authorize('update', $remboursement);

        // montant / caisse_id / beneficiaire_id are not editable — the till
        // balance already moved (UpdateRemboursementRequest excludes them).
        $remboursement->update($request->validated());

        return redirect()->route('backoffice.depenses.index', ['tab' => 'remboursements'])
            ->with('success', __('Refund updated.'));
    }
}
