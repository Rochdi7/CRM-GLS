<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the full replacement fee-line set for an existing inscription
 * (Settings → registrations.manage-fees — the permission the dead
 * InscriptionFeeController used to gate; live here for the first time).
 * Each line may carry `id` (an existing InscriptionFee row to update) or
 * omit it (a brand-new line to create) — lines present in the DB but absent
 * from this payload are deleted by the controller in the same transaction.
 */
final class UpdateInscriptionFeesRequest extends FormRequest
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
            'fee_lines' => ['nullable', 'array'],
            'fee_lines.*.id' => ['nullable', 'integer', 'exists:inscription_fees,id'],
            'fee_lines.*.frais_id' => ['nullable', 'integer', 'exists:frais,id'],
            'fee_lines.*.nom' => ['required', 'string', 'max:150'],
            'fee_lines.*.montant_initial' => ['nullable', 'numeric', 'min:0'],
            'fee_lines.*.remise_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fee_lines.*.remise_montant' => ['nullable', 'numeric', 'min:0'],
            'fee_lines.*.date_echeance' => ['nullable', 'date'],
            'fee_lines.*.note' => ['nullable', 'string'],
        ];
    }
}
