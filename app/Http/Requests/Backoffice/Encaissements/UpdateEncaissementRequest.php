<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use App\Models\Encaissement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⚠ Editing a payment is audit-logged (LogsActivity on the model).
 * `montant` and `caisse_id` are deliberately NOT editable after creation —
 * correcting a wrong amount/till must go through a remboursement + new
 * encaissement so the money trail stays intact.
 */
final class UpdateEncaissementRequest extends FormRequest
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
            'methode' => ['required', Rule::in(Encaissement::METHODES)],
            'date_paiement' => ['required', 'date'],
            'numero_cheque' => ['nullable', 'required_if:methode,'.Encaissement::METHODE_CHEQUE, 'string', 'max:50'],
            // Free text — the Banques catalog (Paramètres → Banques) only
            // feeds the form's autocomplete suggestions, never a hard
            // constraint on what can be typed.
            'banque' => ['nullable', 'required_if:methode,'.Encaissement::METHODE_CHEQUE, 'string', 'max:100'],
            'date_echeance_cheque' => ['nullable', 'required_if:methode,'.Encaissement::METHODE_CHEQUE, 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
