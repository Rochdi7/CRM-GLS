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
            'statut' => ['required', Rule::in(Frais::STATUTS)],
        ];
    }
}
