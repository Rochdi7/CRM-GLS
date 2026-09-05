<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Students;

use App\Models\Encaissement;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Déplacement d'un encaissement vers le frais d'une autre inscription
 * (super-admin, `payments.move-fee`).
 *
 * `fee_id` est NULLABLE : l'absence de cible signifie « détacher », et
 * l'argent redevient une avance libre. Aucune règle ici ne vérifie que le
 * frais appartient bien à l'étudiant ni qu'il reste assez à payer — ces
 * garde-fous lisent des soldes, donc ils vivent dans la transaction
 * verrouillée de DeplacerEncaissementVersFrais (CLAUDE.md §11), pas dans une
 * validation évaluée avant.
 */
final class DeplacerEncaissementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('movePayment', Encaissement::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'encaissement_id' => ['required', 'integer', 'exists:encaissements,id'],
            'fee_id' => ['nullable', 'integer', 'exists:inscription_fees,id'],
        ];
    }
}
