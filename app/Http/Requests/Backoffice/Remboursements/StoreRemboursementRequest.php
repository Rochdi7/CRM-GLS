<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Remboursements;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `reference` is system-generated and `agent_id` comes from the
 * authenticated employee — neither is accepted from the request.
 */
final class StoreRemboursementRequest extends FormRequest
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
            'beneficiaire_id' => ['required', 'exists:students,id'],
            'caisse_id' => ['required', 'exists:caisses,id'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'date_remboursement' => ['required', 'date'],
            'motif' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];
    }
}
