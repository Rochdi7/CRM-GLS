<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Employees;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Note: `reference` is system-generated (ReferenceGenerator) and `user_id`
 * is set by EmployeeObserver (auto-credentials) — neither is accepted here.
 */
final class StoreEmployeeRequest extends FormRequest
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
            'prenom' => ['required', 'string', 'max:100'],
            'categorie' => ['required', Rule::in(Employee::CATEGORIES)],
            'statut' => ['required', Rule::in(Employee::STATUTS)],
            'telephone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'etablissement_id' => ['nullable', 'exists:etablissements,id'],
        ];
    }
}
