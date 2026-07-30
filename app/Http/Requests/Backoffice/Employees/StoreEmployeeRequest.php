<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Employees;

use App\Models\Employee;
use App\Support\Phone\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Full rule set mirrored from EmployeesIndex::rules() (Livewire) — the
 * previous version of this request only covered a narrower legacy subset
 * (no sexe/whatsapp/photo/dates/salaire/note/adresse) used by the retired
 * Blade EmployeeController; this is now the ONE rule set actually enforced,
 * shared by both the Livewire page and the new Inertia/React endpoint.
 *
 * Note: `reference` is system-generated (ReferenceGenerator) and `user_id`
 * is set by EmployeeObserver (auto-credentials) — neither is accepted here.
 * `etablissement_id` is validated as an existing center id, but the
 * controller — not this request — decides whether the client-submitted
 * value is actually honored (only when the top-bar context is "All
 * centers"; otherwise the controller forces CurrentContext::etablissementId()
 * server-side, matching EmployeesIndex::create()'s own auto-scoping).
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
