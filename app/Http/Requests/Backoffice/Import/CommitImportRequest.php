<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Import;

use Illuminate\Foundation\Http\FormRequest;

final class CommitImportRequest extends FormRequest
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
            'selected_row_ids' => ['required', 'array', 'min:1'],
            'selected_row_ids.*' => ['integer'],
        ];
    }
}
