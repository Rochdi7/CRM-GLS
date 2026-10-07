<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Virements;

use Illuminate\Foundation\Http\FormRequest;

/** Refus d'un virement par le comptable — motif OBLIGATOIRE (il reste sur la ligne). */
final class RefuserVirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['motif' => __('reason')];
    }
}
