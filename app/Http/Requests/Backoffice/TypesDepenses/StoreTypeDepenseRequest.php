<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\TypesDepenses;

use App\Models\TypeDepense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `is_system` is NOT accepted from the form — system types are seeded once
 * by TypeDepenseSeeder; the admin form only ever creates custom types
 * (schema §12).
 */
final class StoreTypeDepenseRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:100', 'unique:types_depenses,nom'],
            'statut' => ['required', Rule::in(TypeDepense::STATUTS)],
        ];
    }
}
