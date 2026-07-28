<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Groups;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreGroupRequest extends FormRequest
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
            'niveau' => ['required', Rule::in(Group::NIVEAUX)],
            'enseignant_id' => ['nullable', 'exists:employees,id'],
            'salle_id' => ['nullable', 'exists:salles,id'],
            'etablissement_id' => ['nullable', 'exists:etablissements,id'],
            'annee_scolaire_id' => ['nullable', 'exists:annees_scolaires,id'],
            'capacite_max' => ['nullable', 'integer', 'min:1'],
            // A new group always starts at the beginning of its lifecycle.
            'statut' => ['required', Rule::in([Group::STATUT_PRE_INSCRIPTION, Group::STATUT_EN_FORMATION])],
            'date_debut_formation' => ['nullable', 'date'],
            'date_fin_formation' => ['nullable', 'date', 'after_or_equal:date_debut_formation'],
        ];
    }
}
