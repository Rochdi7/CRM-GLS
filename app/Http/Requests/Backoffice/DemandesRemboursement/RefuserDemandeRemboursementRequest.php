<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\DemandesRemboursement;

use Illuminate\Foundation\Http\FormRequest;

/** Refus d'une demande de remboursement — motif OBLIGATOIRE (il reste sur la ligne). */
final class RefuserDemandeRemboursementRequest extends FormRequest
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
