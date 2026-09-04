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
            // ⚠ Mirrors GetCaisseTransfersList::caisseOptions(): a
            // destination the screen offers must be one the request accepts.
            // The two disagreed once — the list matched the holder's assigned
            // centres while this still ran AccessibleCaisse (which matches
            // `caisses.etablissement_id`) — and the user was refused a till
            // the page had just offered.
            'caisse_destination_id' => ['required', 'exists:caisses,id', new \App\Rules\CaisseDeServiceAccessible],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string'],
        ];
    }
}
