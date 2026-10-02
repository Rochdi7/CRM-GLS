<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Auth;

use App\Http\Requests\Backoffice\Auth\Concerns\RefusesInactiveAccount;
use Illuminate\Foundation\Http\FormRequest;

final class ForgotPasswordRequest extends FormRequest
{
    use RefusesInactiveAccount;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }
}
