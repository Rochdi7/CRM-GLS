<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Etablissements;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateEtablissementRequest extends FormRequest
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
            'nom_centre' => ['required', 'string', 'max:150'],
            'ville' => ['required', 'string', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'siege_social' => ['sometimes', 'boolean'],
        ];
    }
}
