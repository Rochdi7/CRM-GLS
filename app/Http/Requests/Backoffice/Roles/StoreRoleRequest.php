<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Roles;

use App\Support\Authorization\PermissionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Create a role — mirrors App\Livewire\Backoffice\Roles\RoleForm::save()'s
 * create branch exactly. `name` is the raw, user-typed machine name; it is
 * slugified via Str::slug() BEFORE validation runs (prepareForValidation),
 * matching the Livewire component's `$this->name = Str::slug($this->name);`
 * done just before ->validate(). The controller re-authorizes `roles.create`
 * itself — this Form Request's authorize() only gates the HTTP shape, per
 * the module's stated rule ("gate purely on permission strings", enforced in
 * the controller where the permission literal is easiest to audit).
 */
final class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge(['name' => Str::slug((string) $this->input('name'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'name' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('roles', 'name'),
            ],
            'permissions' => ['array'],
            // superAdminOnly() (deletes, approvals, system settings…) can never
            // sit on a role — matrix() strips them from presets, the form must
            // refuse them too (audit SEC-08).
            'permissions.*' => ['string', Rule::in(array_values(array_diff(PermissionRegistry::grantable(), PermissionRegistry::superAdminOnly())))],
        ];
    }
}
