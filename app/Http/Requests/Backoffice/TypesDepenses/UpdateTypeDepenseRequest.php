<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\TypesDepenses;

use App\Models\TypeDepense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * System types (is_system = true) are protected — the controller rejects
 * edits to them entirely; this request only validates custom-type edits.
 */
final class UpdateTypeDepenseRequest extends FormRequest
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
                'required', 'string', 'max:100',
                Rule::unique('types_depenses', 'nom')->ignore($this->route('types_depense')),
            ],
            'statut' => ['required', Rule::in(TypeDepense::STATUTS)],
        ];
    }
}
