<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use Illuminate\Foundation\Http\FormRequest;

/**
 * "Convertir en avance" — which inscription and which of its payments to
 * detach from their fees. Ownership (each payment's fee really belongs to
 * this inscription) is enforced in ConvertirEncaissementsEnAvance, inside
 * the same transaction.
 */
final class ConvertAvanceRequest extends FormRequest
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
            'inscription_id' => ['required', 'integer', 'exists:inscriptions,id'],
            'encaissement_ids' => ['required', 'array', 'min:1'],
            'encaissement_ids.*' => ['required', 'integer', 'exists:encaissements,id'],
            // Partial conversion (« scinder ») — the amount to RELEASE per
            // ticked payment, keyed by encaissement id. An absent or empty
            // entry means the whole row. The upper bound (≤ the row's own
            // montant) is checked in the action on the LOCKED row, not here.
            'montants' => ['sometimes', 'array'],
            'montants.*' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'encaissement_ids.required' => __('Select at least one payment to convert.'),
            'encaissement_ids.min' => __('Select at least one payment to convert.'),
        ];
    }
}
