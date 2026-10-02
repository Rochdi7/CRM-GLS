<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Auth;

use App\Http\Requests\Backoffice\Auth\Concerns\RefusesInactiveAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class ResetPasswordRequest extends FormRequest
{
    use RefusesInactiveAccount;

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
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
