<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Profile;

use App\Support\Phone\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Same rules as the Livewire ProfilePage::updateProfile() this replaces —
 * a user edits only their own name/email/phone/whatsapp, never roles,
 * permissions, center, is_active, or another user's record.
 */
final class UpdateProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone_pays' => ['required', Rule::in(array_keys(Countries::LIST))],
            'telephone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
        ];
    }
}
