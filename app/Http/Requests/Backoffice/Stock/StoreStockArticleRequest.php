<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Stock;

use App\Models\StockArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `reference` is system-generated (ReferenceGenerator, ART-…) and `quantite`
 * starts at 0 — the initial stock is recorded as an Entrée movement, never
 * typed into the article form. etablissement_id comes from the active
 * working context when a center is selected; under « Tous les centres » it
 * is chosen in the form (required — stock is always per center).
 */
final class StoreStockArticleRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:150'],
            'stock_type_id' => ['required', 'exists:stock_types,id'],
            // Only honoured when the context is « Tous les centres » — with a
            // center selected in the top bar the controller forces that one.
            'etablissement_id' => ['nullable', 'integer', 'exists:etablissements,id'],
            'seuil_alerte' => ['nullable', 'integer', 'min:0'],
            'statut' => ['required', Rule::in(StockArticle::STATUTS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
