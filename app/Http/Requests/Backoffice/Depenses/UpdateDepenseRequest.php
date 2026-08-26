<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Depenses;

use App\Http\Requests\Backoffice\Depenses\Concerns\PaiementProfRules;
use App\Models\Depense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⚠ `montant` and `caisse_id` are deliberately NOT editable after creation —
 * the till balance already moved; corrections need a compensating entry.
 * Edits are audit-logged (LogsActivity on the model).
 */
final class UpdateDepenseRequest extends FormRequest
{
    use PaiementProfRules;

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
            ...$this->typeDependentRules(),
            'type_depense_id' => ['required', 'exists:types_depenses,id'],
            // Nullable on edit: rows predating the field stay correctable.
            'methode_paiement' => ['nullable', Rule::in(Depense::METHODES)],
            'date_depense' => ['required', 'date'],
            'mots_cles' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'justificatifs.*' => ['file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ];
    }
}
