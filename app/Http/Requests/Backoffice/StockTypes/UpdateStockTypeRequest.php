<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\StockTypes;

use App\Models\StockType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * System types (is_system = true) are protected — the controller rejects
 * edits to them entirely; this request only validates custom-type edits.
 */
final class UpdateStockTypeRequest extends FormRequest
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
            'nom' => [
                'required', 'string', 'max:100',
                Rule::unique('stock_types', 'nom')->ignore($this->route('stock_type')),
            ],
            'statut' => ['required', Rule::in(StockType::STATUTS)],
        ];
    }
}
