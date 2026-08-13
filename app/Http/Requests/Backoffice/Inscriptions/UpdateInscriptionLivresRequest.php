<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the full submitted book selection for an existing registration
 * — AssignerLivresInscription diffs this against what's already assigned
 * (inscription_livres), so a book already given stays untouched and only
 * newly added/removed books move stock.
 */
final class UpdateInscriptionLivresRequest extends FormRequest
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
            'livre_ids' => ['nullable', 'array'],
            'livre_ids.*' => ['integer', 'exists:stock_articles,id'],
        ];
    }
}
