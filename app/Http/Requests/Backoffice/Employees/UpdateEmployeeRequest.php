<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Employees;

use App\Models\Employee;
use App\Support\Phone\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Full rule set mirrored from EmployeesIndex::rules() (Livewire) — see
 * StoreEmployeeRequest's note on why this replaces the previous narrower
 * legacy rule set. `reference` and `user_id` are still never accepted here.
 */
final class UpdateEmployeeRequest extends FormRequest
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
            'sexe' => ['required', Rule::in(Employee::SEXES)],
            'categorie' => ['required', Rule::in(Employee::CATEGORIES)],
            'statut' => ['required', Rule::in(Employee::STATUTS)],
            'phone_pays' => ['nullable', Rule::in(array_keys(Countries::LIST))],
            'telephone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'date_embauche' => ['nullable', 'date'],
            'salaire' => ['nullable', 'numeric', 'min:0'],
            'etablissement_id' => ['nullable', 'exists:etablissements,id'],
        ];
    }
}
