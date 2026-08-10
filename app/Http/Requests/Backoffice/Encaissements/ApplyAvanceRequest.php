<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use Illuminate\Foundation\Http\FormRequest;

final class ApplyAvanceRequest extends FormRequest
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
            'fee_id' => ['required', 'exists:inscription_fees,id'],
            'montant' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
