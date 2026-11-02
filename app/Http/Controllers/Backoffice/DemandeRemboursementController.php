<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Finance\Actions\ApprouverDemandeRemboursement;
use App\Domain\Finance\Actions\RefuserDemandeRemboursement;
use App\Http\Controllers\Backoffice\Concerns\AssertsContextScope;
use App\Http\Controllers\Backoffice\Concerns\RedirectsPreservingFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backoffice\DemandesRemboursement\ApprouverDemandeRemboursementRequest;
use App\Http\Requests\Backoffice\DemandesRemboursement\RefuserDemandeRemboursementRequest;
use App\Models\DemandeRemboursement;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Décision sur une demande de remboursement (02/11/2026). La liste est
 * servie par DepenseController@index (onglet Remboursements) ; la demande
 * elle-même naît de RemboursementController@store.
 *
 * Approuver / refuser = `refunds.approve` (directeur, + super-admin), borné
 * aux centres affectés par DemandeRemboursementPolicy. Choisir TPE /
 * Virement = `refunds.choose-method` (super-admin) — dérivé ICI de la
 * permission, jamais d'un champ du formulaire.
 */
final class DemandeRemboursementController extends Controller
{
    use AssertsContextScope;
    use RedirectsPreservingFilters;

    public function approuver(ApprouverDemandeRemboursementRequest $request, DemandeRemboursement $demande, ApprouverDemandeRemboursement $action): RedirectResponse
    {
        $this->guard($request, $demande);

        $action->handle(
            $demande,
            $this->agentOrFail($request),
            (string) $request->validated('methode'),
            $request->user()->can('refunds.choose-method'),
        );

        return $this->backToListPreservingFilters($request, 'backoffice.depenses.index', ['tab' => 'demandes-remboursement'])
            ->with('success', __('Refund request approved: the refund is now recorded.'));
    }

    public function refuser(RefuserDemandeRemboursementRequest $request, DemandeRemboursement $demande, RefuserDemandeRemboursement $action): RedirectResponse
    {
        $this->guard($request, $demande);

        $action->handle($demande, $this->agentOrFail($request), (string) $request->validated('motif'));

        return $this->backToListPreservingFilters($request, 'backoffice.depenses.index', ['tab' => 'demandes-remboursement'])
            ->with('success', __('Refund request refused.'));
    }

    private function guard(Request $request, DemandeRemboursement $demande): void
    {
        $this->authorize('approve', $demande);
        // Approuver fait sortir de l'argent : le verrou « année clôturée »
        // vaut comme pour toute écriture (§11).
        $this->assertContextAnneeOuverte('demande');
        $this->assertRecordInContext(
            $request,
            'demande',
            $demande->etablissement_id === null ? null : (int) $demande->etablissement_id,
            null,
            __('This refund request belongs to another centre than the active one.'),
            '',
        );
    }

    private function agentOrFail(Request $request): Employee
    {
        $agent = $request->user()->employee;

        if ($agent === null) {
            throw ValidationException::withMessages([
                'demande' => __('Your account is not linked to any employee record.'),
            ]);
        }

        return $agent;
    }
}
