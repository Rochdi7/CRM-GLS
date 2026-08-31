<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use App\Models\Encaissement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⚠ Editing a payment is audit-logged (LogsActivity on the model).
 * `montant`, `caisse_id` AND `methode` are deliberately NOT editable after
 * creation — the method decided which account was credited, so correcting
 * any of the three must go through a remboursement + new encaissement so
 * the money trail stays intact. `methode` is still accepted here only so
 * the edit modal can echo it back; the controller refuses a different value.
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
            'methode' => ['nullable', Rule::in(Encaissement::METHODES)],
            'date_paiement' => [
                $this->user()?->can('payments.update-date') ? 'required' : 'nullable',
                'date',
            ],
            'numero_cheque' => ['nullable', 'string', 'max:50'],
            // Free text — the Banques catalog (Paramètres → Banques) only
            // feeds the form's autocomplete suggestions, never a hard
            // constraint on what can be typed.
            'banque' => ['nullable', 'string', 'max:100'],
            'date_echeance_cheque' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
