<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\CaisseTransfers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQUEST step of the two-step transfer flow (structure doc §7).
 * `reference`, solde snapshots and `requested_by` are set by the controller;
 * balances do NOT move until a different employee validates.
 *
 * `caisse_source_id` is NEVER accepted from the client: the source is always
 * the acting employee's OWN till (even for super-admins), derived
 * server-side in CaisseTransferController::store() — same rule as
 * EncaissementController's server-derived till.
 */
final class StoreCaisseTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The destination must be an accessible till — the form's
            // options are already center-scoped
            // (GetCaisseTransfersList::caisseOptions); this closes the
            // tampered-request path (Phase 12 security). "different from the
            // source" is checked in the controller, since the source is
            // server-derived.
            // ⚠ No AccessibleCaisse here — deliberate (04/09/2026), and it must
            // stay in step with GetCaisseTransfersList::caisseOptions(), which
            // applies no centre filter either. A destination the screen offers
            // must be a destination the request accepts.
            //
            // A transfer's real control is the RECIPIENT, not the centre:
            // balances move only when the employee owning the destination till
            // accepts it (ValiderTransfertCaisse — no super-admin bypass,
            // CLAUDE.md §11), and both legs are journaled with their own
            // centre. Refusing a cross-centre destination here blocked
            // legitimate hand-overs while protecting nothing: the money had
            // not moved yet, and the person receiving it still has to say yes.
            // Refunds keep AccessibleCaisse — they drain a till immediately,
            // with nobody to confirm.
            'caisse_destination_id' => ['required', 'exists:caisses,id'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string'],
        ];
    }
}
