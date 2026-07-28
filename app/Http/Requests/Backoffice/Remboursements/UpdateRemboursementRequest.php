<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Remboursements;

use Illuminate\Foundation\Http\FormRequest;

/**
 * ⚠ `montant` and `caisse_id` are deliberately NOT editable after creation —
 * the till balance already moved. Edits are audit-logged.
 */
final class UpdateRemboursementRequest extends FormRequest
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
            'date_remboursement' => ['required', 'date'],
            'motif' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];
    }
}
