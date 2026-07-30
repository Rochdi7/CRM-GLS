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

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'is_active' => ['boolean'],
        ];
    }
}
