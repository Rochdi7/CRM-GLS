<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Groups;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⚠ Transition to "Fin de formation" is allowed here but the controller MUST
 * route it through Group::archiverCommeTermine() — never a raw update — so
 * the groups_historique snapshot is written in the same transaction.
 */
final class UpdateGroupRequest extends FormRequest
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
            'statut' => ['required', Rule::in(Group::STATUTS)],
            'date_debut_formation' => ['nullable', 'date'],
            'date_fin_formation' => ['nullable', 'date', 'after_or_equal:date_debut_formation'],
        ];
    }
}
