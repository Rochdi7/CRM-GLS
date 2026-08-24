<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Import;

use Illuminate\Foundation\Http\FormRequest;

final class PeekInscriptionGroupesRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            // Honored only in « Tous les centres » mode — otherwise the
            // active context decides (ResolvesImportScope), never the client.
            'etablissement_id' => ['nullable', 'integer', 'exists:etablissements,id'],
        ];
    }
}
