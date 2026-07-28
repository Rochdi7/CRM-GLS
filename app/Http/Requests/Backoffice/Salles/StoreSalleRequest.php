<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Salles;

use App\Models\Salle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSalleRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:100'],
            'etablissement_id' => ['required', 'exists:etablissements,id'],
            'capacite' => ['nullable', 'integer', 'min:1'],
            'statut' => ['required', Rule::in(Salle::STATUTS)],
        ];
    }
}
