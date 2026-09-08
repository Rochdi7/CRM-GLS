<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Employees;

use App\Models\Employee;
use App\Models\Role;
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
            'categorie' => [
                'required',
                Rule::in(Employee::CATEGORIES),
                // « Responsable de système » ⇒ super-admin (EmployeeObserver),
                // so only a super-admin may hand it out — same invariant as
                // UserAuthorizationService (CLAUDE.md §16).
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === Employee::CATEGORIE_RESPONSABLE_SYSTEME
                        && ! $this->user()?->hasRole(Role::SUPER_ADMIN)) {
                        $fail(__('Only a super-admin can assign the « Responsable de système » category.'));
                    }
                },
            ],
            'statut' => ['required', Rule::in(Employee::STATUTS)],
            'phone_pays' => ['nullable', Rule::in(array_keys(Countries::LIST))],
            'telephone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('employee')?->user_id)],
            'adresse' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'date_embauche' => ['nullable', 'date'],
            'salaire' => ['nullable', 'numeric', 'min:0'],
            // At least ONE center is mandatory — an employee is never
            // unaffected. The controller still decides WHICH ids are honored
            // (a center-locked admin is forced to its own context center).
            'etablissement_ids' => ['required', 'array', 'min:1'],
            'etablissement_ids.*' => ['integer', 'distinct', 'exists:etablissements,id'],
            // The PRIMARY centre (« Centre principal ») — where the employee
            // is based and where its Caisse lives. Optional: when absent the
            // model keeps the current primary, or falls back to the first
            // assigned centre (Employee::syncEtablissements). It MUST be one
            // of the centres submitted above — a primary outside the
            // assignment would put the till in a centre the employee cannot
            // reach. Changing it never moves money (CLAUDE.md §11).
            'etablissement_principal_id' => [
                'nullable',
                'integer',
                'exists:etablissements,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $assigned = array_map('intval', (array) $this->input('etablissement_ids', []));

                    if (! in_array((int) $value, $assigned, true)) {
                        $fail(__('The primary center must be one of the assigned centers.'));
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'etablissement_ids.required' => __('Select at least one center.'),
            'etablissement_ids.min' => __('Select at least one center.'),
        ];
    }
}
