<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Same rules as App\Livewire\Backoffice\Users\UsersIndex::rules() — users are
 * never created here, only edited (name/email/username/is_active). Real
 * authorization happens in the controller action ($this->authorize(...)),
 * not here — this Form Request only validates shape, matching the Livewire
 * component it replaces.
 */
final class UpdateUserRequest extends FormRequest
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
        $userId = $this->route('user')?->id;

        // A login linked to an employee takes its name from the employee
        // (SynchroniserNomEmploye) — renamed on the Employees screen or in
        // Profil, never here, or the login and the caisse would diverge.
        $linked = $this->route('user')?->employee()->exists() ?? false;

        return [
            'name' => $linked ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'is_active' => ['boolean'],
        ];
    }
}
