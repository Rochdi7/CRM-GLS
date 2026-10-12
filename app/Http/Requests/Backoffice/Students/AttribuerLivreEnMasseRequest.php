<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Students;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Onglet « Livres » des Étudiants : un livre, un groupe, les inscriptions
 * cochées. La portée (groupe ⇄ inscriptions ⇄ centre du livre) est
 * revérifiée par AttribuerLivreEnMasse, sous verrou.
 */
final class AttribuerLivreEnMasseRequest extends FormRequest
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
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'stock_article_id' => ['required', 'integer', 'exists:stock_articles,id'],
            'inscription_ids' => ['required', 'array', 'min:1'],
            'inscription_ids.*' => ['integer', 'distinct'],
        ];
    }
}
