<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\AnneesScolaires;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAnneeScolaireRequest extends FormRequest
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
                'required', 'string', 'max:20',
                Rule::unique('annees_scolaires', 'nom')->ignore($this->route('annees_scolaire')),
            ],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'par_defaut' => ['sometimes', 'boolean'],
            'inscription_ouverte' => ['sometimes', 'boolean'],
            'cloturee' => ['sometimes', 'boolean'],
        ];
    }
}
