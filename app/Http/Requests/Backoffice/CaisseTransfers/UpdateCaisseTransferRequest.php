<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\CaisseTransfers;

use App\Models\CaisseTransfer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A transfer can only be edited while "En attente", and only its note or a
 * cancellation. Validation (approval) is a separate controller action —
 * NOT this request — because it moves real money and requires a different
 * employee than the requester.
 */
final class UpdateCaisseTransferRequest extends FormRequest
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
            'note' => ['nullable', 'string'],
            'statut' => ['sometimes', Rule::in([CaisseTransfer::STATUT_ANNULE])],
        ];
    }
}
