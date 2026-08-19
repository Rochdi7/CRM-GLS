<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Frais;

use App\Models\Frais;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreFraisRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:150', 'unique:frais,nom'],
            'montant_defaut' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'statut' => ['required', Rule::in(Frais::STATUTS)],
        ];
    }
}
