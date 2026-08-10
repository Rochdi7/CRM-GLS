<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Attendance;

use App\Models\Seance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * etablissement_id / annee_scolaire_id are never form inputs — the
 * controller always inherits them from the séance's group.
 */
final class StoreSeanceRequest extends FormRequest
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
            'group_id' => ['required', 'exists:groups,id'],
            'date_seance' => ['required', 'date'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'heure_fin' => ['nullable', 'date_format:H:i', 'after:heure_debut'],
            'enseignant_id' => ['nullable', 'exists:employees,id'],
            'statut' => ['required', Rule::in(Seance::STATUTS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
