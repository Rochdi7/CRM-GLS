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
            'etablissement_id' => ['required', 'integer', 'exists:etablissements,id'],
            'annee_scolaire_id' => ['required', 'integer', 'exists:annees_scolaires,id'],
            'operateur_mapping' => ['present', 'array'],
            'operateur_mapping.*.label' => ['required', 'string'],
            'operateur_mapping.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
        ];
    }
}
