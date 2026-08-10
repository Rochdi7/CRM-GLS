<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Banques;

use App\Models\Banque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreBanqueRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:150', 'unique:banques,nom'],
            'statut' => ['required', Rule::in(Banque::STATUTS)],
        ];
    }
}
