<?php

declare(strict_types=1);

namespace App\Domain\Finance\Support;

use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Cheque;
use App\Models\Encaissement;
use App\Services\CaisseProvisioner;
use App\Services\Context\CurrentContext;
use Illuminate\Validation\ValidationException;

/**
 * The ONE place that decides which `caisses` row a money record lands in.
 *
 *  - Espèces → the acting employee's own physical till (never chosen
 *    client-side, self-healed if the account predates the provisioner).
 *  - TPE / Chèque / Virement → the centre's account for that method.
 *
 * The centre of a non-cash record is the centre the employee is WORKING IN
 * (the top-bar context, CLAUDE.md §11 « creates inherit etablissement_id
 * from the active context ») — that is physically where the card terminal
 * sits and where the cheque was handed over. When the context is « Tous les
 * centres » (global users only) the employee's PRIMARY centre fills in.
 * A caller that already knows the centre (the legacy import, whose batch is
 * centre-scoped) passes it explicitly.
 *
 * The resolved id is stored on the row and is immutable from then on, so
 * every later step (cancellation, approval, avance application) reverses or
 * follows the SAME account without re-deriving anything.
 */
final class CaisseResolver
{
    public function __construct(
        private readonly CaisseProvisioner $provisioner,
        private readonly CurrentContext $context,
    ) {}

    public function resolveFor(Employee $agent, string $methode, ?int $etablissementId = null): Caisse
    {
        if ($methode === Encaissement::METHODE_ESPECES) {
            return $this->tillOf($agent);
        }

        if (! in_array($methode, Caisse::TYPES_METHODE, true)) {
            throw ValidationException::withMessages([
                'methode' => __('Unknown payment method.'),
            ]);
        }

        $centreId = $etablissementId ?? $this->context->etablissementId() ?? $agent->etablissement_id;

        if ($centreId === null) {
            // No context centre and an employee without a primary centre:
            // there is no account to put the money in. Refusing is the only
            // safe answer — silently using the cash till is the bug this
            // class replaced.
            throw ValidationException::withMessages([
                'methode' => __('Select a centre in the top bar before recording a non-cash payment.'),
            ]);
        }

        return $this->provisioner->compteMethodeFor((int) $centreId, $methode);
    }

    /**
     * The account a REFUND debits.
     *
     * Rule (24/08/2026): a refund is cash leaving the agent's physical till,
     * whatever way the money came in — with ONE exception. When the refund
     * reverses a payment funded by a chèque that has since been REJECTED,
     * that money never existed: the bank bounced it, the till never held it,
     * only the centre's Chèque account was credited. Debiting the till would
     * make the cashier's cash count wrong by the cheque's amount. So the
     * reversal hits the account the payment actually credited.
     */
    public function forRemboursement(Employee $agent, ?Encaissement $encaissement): Caisse
    {
        if ($encaissement !== null
            && $encaissement->cheque_id !== null
            && $encaissement->cheque?->statut === Cheque::STATUT_REJETE
            && $encaissement->caisse !== null
            && $encaissement->caisse->isCompteMethode()) {
            return $encaissement->caisse;
        }

        return $this->tillOf($agent);
    }

    /**
     * The employee's physical till (Espèces) — type « Caissière » ONLY.
     *
     * Never `caisses()->first()`: that relation also returns an « Externe »
     * safe the employee was made responsable of, and money would then be
     * routed into the safe (audit 24/08/2026). Employee::till() filters on
     * the type; the provisioner self-heals an account predating it.
     */
    public function tillOf(Employee $agent): Caisse
    {
        return $agent->till()->first()
            ?? $this->provisioner->provisionFor($agent)
            ?? $agent->till()->firstOrFail();
    }
}
