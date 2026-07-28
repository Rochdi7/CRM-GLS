<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use App\Models\Encaissement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `reference` is system-generated and `agent_id` comes from the authenticated
 * employee. Cheque fields are required only when methode = Chèque (schema §11).
 */
final class StoreEncaissementRequest extends FormRequest
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
            'student_id' => ['required', 'exists:students,id'],
            'inscription_fee_id' => ['required', 'exists:inscription_fees,id'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'methode' => ['required', Rule::in(Encaissement::METHODES)],
            'date_paiement' => ['required', 'date'],
            'caisse_id' => ['required', 'exists:caisses,id'],
            'numero_cheque' => ['nullable', 'required_if:methode,'.Encaissement::METHODE_CHEQUE, 'string', 'max:50'],
            'banque' => ['nullable', 'required_if:methode,'.Encaissement::METHODE_CHEQUE, 'string', 'max:100'],
            'date_echeance_cheque' => ['nullable', 'required_if:methode,'.Encaissement::METHODE_CHEQUE, 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
