<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Frais;

use App\Models\Frais;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFraisRequest extends FormRequest
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
            'nom' => [
                'required', 'string', 'max:150',
                Rule::unique('frais', 'nom')->ignore($this->route('frai')),
            ],
            // A default amount must itself default: when the key is absent
            // it stays out of validated(), so the column's own 0.00 default
            // applies. Required would break every caller that omits it;
            // nullable would push a null into a NOT NULL column.
            'montant_defaut' => ['sometimes', 'numeric', 'min:0', 'max:9999999.99'],
            'statut' => ['required', Rule::in(Frais::STATUTS)],
            // Per-center pricing lines. Optional: a fee attached to no
            // center simply falls back to montant_defaut everywhere.
            'centres' => ['sometimes', 'array'],
            'centres.*.etablissement_id' => ['required', 'integer', 'exists:etablissements,id'],
            'centres.*.montant' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
        ];
    }
}
