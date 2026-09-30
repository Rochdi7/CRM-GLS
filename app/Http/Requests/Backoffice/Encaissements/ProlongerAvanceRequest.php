<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use Illuminate\Foundation\Http\FormRequest;

/**
 * « Prolonger la validité » d'une avance expirée. La durée n'est pas un
 * champ : elle est fixée par ValiditeAvance (14 jours depuis aujourd'hui).
 * Seul le motif est saisi, et il est obligatoire — c'est ce que le journal
 * conserve pour expliquer pourquoi de l'argent périmé a été rouvert.
 */
final class ProlongerAvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.override-advance-expiry') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
