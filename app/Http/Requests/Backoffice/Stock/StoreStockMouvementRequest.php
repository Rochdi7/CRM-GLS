<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Stock;

use App\Models\StockMouvement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreStockMouvementRequest extends FormRequest
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
            'stock_article_id' => ['required', 'exists:stock_articles,id'],
            'type' => ['required', Rule::in(StockMouvement::TYPES)],
            // Ajustement carries the new total, so 0 is legitimate there;
            // Entrée/Sortie must move at least one unit.
            'quantite' => ['required', 'integer', 'min:0', Rule::when(
                fn ($input) => $input->type !== StockMouvement::TYPE_AJUSTEMENT,
                ['min:1'],
            )],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
