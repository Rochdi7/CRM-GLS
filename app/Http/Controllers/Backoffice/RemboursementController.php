<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Actions\EnregistrerRemboursement;
use App\Domain\Finance\Support\CaisseResolver;
use App\Domain\Finance\Queries\GetStudentPaymentsForRefund;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Requests\Backoffice\Remboursements\StoreRemboursementRequest;
use App\Http\Requests\Backoffice\Remboursements\UpdateRemboursementRequest;
use App\Models\Encaissement;
use App\Models\Remboursement;
use App\Models\Student;
use App\Services\Authorization\CenterAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    use AssertsContextScope;

    /**
     * A student's fee-targeted payments — the create form's "which payment
     * are we refunding?" cascade (GetStudentPaymentsForRefund). Gated the
     * same as creating a refund.
     */
    public function studentPayments(Request $request, int $student, GetStudentPaymentsForRefund $getStudentPaymentsForRefund): JsonResponse
    {
        $this->authorize('create', Remboursement::class);
        $this->assertCenterAccess($request, Student::query()->findOrFail($student)->etablissement_id);

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

        $data = $request->validated();

        // Centre isolation (same rule as studentPayments() above and as
        // EncaissementController@store): the beneficiary must be a student
        // of a centre the cashier can reach. Without it a tampered
        // beneficiaire_id refunded another centre's student out of this
        // till — the linked payment (if any) is checked by the action to
        // belong to that same student, so this one check covers both.
        $this->assertStudentInContext($request, Student::query()->findOrFail((int) $data['beneficiaire_id']), 'beneficiaire_id');

        $encaissement = ! empty($data['encaissement_id'])
            ? Encaissement::query()->with(['cheque', 'caisse'])->find((int) $data['encaissement_id'])
            : null;

        // Which till the money leaves is now the CASHIER's choice
        // (03/09/2026): deriving it from the acting employee meant a
        // Salé-homed cashier refunding a Rabat student drained a Salé till,
        // and the row was then invisible on both centres — so the same
        // 300 DH went out twice. The choice is validated for centre reach
        // and cash-only in StoreRemboursementRequest.
        //
        // ONE case still overrides it: reversing a payment funded by a
        // chèque that has since been REJECTED. That money never reached any
        // till, so it must come back out of the centre's Chèque account —
        // an accounting invariant, not a preference, so it wins over the
        // submitted value rather than being offered in the dropdown.
        $resolved = app(CaisseResolver::class)->forRemboursement($agent, $encaissement);
        $chequeReversal = $resolved->isCompteMethode();

        // No caisse_id submitted ⇒ the acting employee's own till, exactly as
        // before this change. Keeping that fallback is what stops an omitted
        // field from failing the whole submission silently.
        $caisseId = $chequeReversal || empty($data['caisse_id'])
            ? $resolved->id
            : (int) $data['caisse_id'];

        $action->handle([
            ...$data,
            'caisse_id' => $caisseId,
        ], $agent);

        return redirect()->route('backoffice.depenses.index', ['tab' => 'remboursements'])
            ->with('success', __('Refund recorded.'));
    }

    public function update(UpdateRemboursementRequest $request, Remboursement $remboursement): RedirectResponse
    {
        $this->authorize('update', $remboursement);

        // A remboursement carries no annee_scolaire_id (date-windowed, §11),
        // so the ACTIVE year is what the closed-year lock checks here.
        $this->assertContextAnneeOuverte('note');

        // montant / caisse_id / beneficiaire_id are not editable — the till
        // balance already moved (UpdateRemboursementRequest excludes them).
        $remboursement->update($request->validated());

        return redirect()->route('backoffice.depenses.index', ['tab' => 'remboursements'])
            ->with('success', __('Refund updated.'));
    }

    /**
     * Center scope for lookups and writes that hang off another module's
     * record: the cashier needs no `registrations.view`/`students.view`
     * permission to take money, only access to the record's center
     * (CenterAccessService — same rule the policies use).
     */
    private function assertCenterAccess(Request $request, ?int $etablissementId): void
    {
        if (! app(CenterAccessService::class)->canAccessCenter($request->user(), $etablissementId)) {
            abort(403);
        }
    }
}
