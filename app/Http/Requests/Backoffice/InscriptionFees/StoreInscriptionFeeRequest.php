<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\InscriptionFees;

use App\Models\InscriptionFee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInscriptionFeeRequest extends FormRequest
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
            'inscription_id' => ['required', 'exists:inscriptions,id'],
            'nom' => ['required', 'string', 'max:150'],
            'montant' => ['required', 'numeric', 'min:0'],
            'date_echeance' => ['required', 'date'],
            'statut' => ['required', Rule::in(InscriptionFee::STATUTS)],
        ];
    }
}
