<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Users;

use App\Support\Authorization\PermissionRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shallow shape validation only, matching
 * App\Livewire\Backoffice\Users\ManageAuthorization::save() — this Form
 * Request does NOT check role/permission existence, super-admin invariants,
 * or the users.assign-permissions privilege for direct permissions; those
 * deep rules stay exclusively in
 * App\Services\Authorization\UserAuthorizationService::syncAuthorization(),
 * which throws ValidationException on violation (surfaced the normal
 * Inertia way: redirect back with errors).
 */
final class SyncUserAuthorizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'roles' => ['array'],
            'roles.*' => ['string'],
            'directPermissions' => ['array'],
            // centers.access-all is never grantable (super-admin only via
            // Gate::before) — refused here AND in the service.
            'directPermissions.*' => ['string', Rule::notIn([PermissionRegistry::GLOBAL_CENTER_ACCESS])],
        ];
    }
}
