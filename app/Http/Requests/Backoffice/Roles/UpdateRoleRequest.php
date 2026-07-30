<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Roles;

use App\Support\Authorization\PermissionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update a role — `name` (machine name) is deliberately NOT a validated or
 * writable field here, matching App\Livewire\Backoffice\Roles\RoleForm::save()'s
 * update branch exactly:
 *   `$this->role->update(['label' => $validated['label']]);` — only `label`
 * is ever written; `name` stays whatever it was at creation. If the frontend
 * still submits the (disabled/read-only) name input's value, it is simply
 * absent from validated()/rules() below and therefore never reaches the
 * model — accepted-and-ignored, not rejected, so a stray `name` key in the
 * payload never causes a validation error.
 */
final class UpdateRoleRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:100'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(PermissionRegistry::names())],
        ];
    }
}
