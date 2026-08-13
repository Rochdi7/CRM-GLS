<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\StockTypes;

use App\Models\StockType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `is_system` is NOT accepted from the form — system types are seeded once
 * by StockTypeSeeder; the admin form only ever creates custom types.
 */
final class StoreStockTypeRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:100', 'unique:stock_types,nom'],
            'statut' => ['required', Rule::in(StockType::STATUTS)],
        ];
    }
}
