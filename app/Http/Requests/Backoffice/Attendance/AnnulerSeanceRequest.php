<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Attendance;

use Illuminate\Foundation\Http\FormRequest;

final class AnnulerSeanceRequest extends FormRequest
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
            'motif' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
