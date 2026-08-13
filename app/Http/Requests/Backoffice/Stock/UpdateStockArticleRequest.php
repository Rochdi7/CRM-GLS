<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Stock;

use App\Models\StockArticle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `quantite` is deliberately absent — it only changes through movements
 * (EnregistrerMouvementStock), never by editing the article.
 */
final class UpdateStockArticleRequest extends FormRequest
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
            'seuil_alerte' => ['nullable', 'integer', 'min:0'],
            'statut' => ['required', Rule::in(StockArticle::STATUTS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
