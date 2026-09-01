<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use Illuminate\Foundation\Http\FormRequest;

final class ModifyGroupInscriptionRequest extends FormRequest
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
            'new_group_id' => ['required', 'exists:groups,id'],
        ];
    }
}
