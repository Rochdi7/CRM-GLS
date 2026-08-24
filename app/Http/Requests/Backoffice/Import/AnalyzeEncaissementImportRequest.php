<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Import;

use Illuminate\Foundation\Http\FormRequest;

final class AnalyzeEncaissementImportRequest extends FormRequest
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
            'operateur_mapping' => ['present', 'array'],
            'operateur_mapping.*.label' => ['required', 'string'],
            'operateur_mapping.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
            // Off by default: attaching money to a cancelled enrolment is a
            // deliberate choice the operator makes, not a silent behaviour.
            'include_inactive_inscriptions' => ['sometimes', 'boolean'],
        ];
    }
}
