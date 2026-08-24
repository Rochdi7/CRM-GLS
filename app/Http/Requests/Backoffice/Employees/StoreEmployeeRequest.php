<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Employees;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
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
 * `etablissement_ids` (an employee may work in SEVERAL centers) is
 * validated as a non-empty list of existing center ids — an employee is
 * never left unassigned. The controller — not this request — narrows the
 * client-submitted list to the acting user's own accessible centers
 * (EmployeeController::resolveCenterIds — intersection with
 * CenterAccessService::accessibleCenterIds; global users keep the full
 * list), so the pivot can never be widened beyond the caller's reach.
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
            'email' => ['nullable', 'email', 'max:255'],
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
            // Optional — EmployeeCredentialService auto-generates one from
            // nom/prenom when left blank. Validated here only if provided.
            'username' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', Rule::unique(User::class, 'username')],
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
