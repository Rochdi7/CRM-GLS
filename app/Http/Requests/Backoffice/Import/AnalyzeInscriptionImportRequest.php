<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Import;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AnalyzeInscriptionImportRequest extends FormRequest
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
            'groupe_mapping' => ['present', 'array'],
            'groupe_mapping.*.label' => ['required', 'string'],
            'groupe_mapping.*.action' => ['required', Rule::in(['map', 'create'])],
            'groupe_mapping.*.group_id' => ['required_if:groupe_mapping.*.action,map', 'nullable', 'integer', 'exists:groups,id'],
            'groupe_mapping.*.nom' => ['required_if:groupe_mapping.*.action,create', 'nullable', 'string', 'max:150'],
            'groupe_mapping.*.niveau' => ['required_if:groupe_mapping.*.action,create', 'nullable', Rule::in(Group::NIVEAUX)],
        ];
    }
}
