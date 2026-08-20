<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The signed-in user's own avatar. Same rule as the Employees module's
 * `photo` upload (StoreEmployeeRequest) so both write identically-shaped
 * files into the Employee `photo` media collection — no permission gate,
 * a user only ever touches their own linked employee record.
 */
final class UpdatePhotoRequest extends FormRequest
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
            'photo' => ['required', 'image', 'max:2048'],
        ];
    }
}
