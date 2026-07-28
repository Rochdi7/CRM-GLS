<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Depenses;

use App\Models\Depense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `reference` is system-generated and `agent_id` comes from the
 * authenticated employee — neither is accepted from the request.
 */
final class StoreDepenseRequest extends FormRequest
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
            'type_depense_id' => ['required', 'exists:types_depenses,id'],
            'caisse_id' => ['required', 'exists:caisses,id'],
            'group_id' => ['nullable', 'exists:groups,id'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'methode_paiement' => ['required', Rule::in(Depense::METHODES)],
            'date_depense' => ['required', 'date'],
            'reference_facture' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'mots_cles' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];
    }
}
