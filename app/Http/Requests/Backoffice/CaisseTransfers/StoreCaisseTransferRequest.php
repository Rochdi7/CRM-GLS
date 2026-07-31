<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\CaisseTransfers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * REQUEST step of the two-step transfer flow (structure doc §7).
 * `reference`, solde snapshots and `requested_by` are set by the controller;
 * balances do NOT move until a different employee validates.
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
            // Both ends must be accessible tills — the form's options are
            // already center-scoped (GetCaisseTransfersList::caisseOptions);
            // this closes the tampered-request path (Phase 12 security).
            'caisse_source_id' => ['required', 'exists:caisses,id', new \App\Rules\AccessibleCaisse],
            'caisse_destination_id' => ['required', 'exists:caisses,id', 'different:caisse_source_id', new \App\Rules\AccessibleCaisse],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'note' => ['nullable', 'string'],
        ];
    }
}
