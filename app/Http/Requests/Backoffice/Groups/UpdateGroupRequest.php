<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Groups;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates exactly the fields the current Livewire GroupsIndex form exposes
 * (docs/phase-8-students-groups-inventory.md) — no salle_id/capacite_max.
 *
 * ⚠ A raw `statut` of "Fin de formation" is accepted here (same subset as
 * the Livewire form's edit-mode select) but GroupController::update() MUST
 * silently revert it to the group's current status unless the transition
 * happens through Group::archiverCommeTermine() — never a raw update — so
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
            'statut' => ['required', Rule::in(Group::STATUTS)],
            'date_debut_formation' => ['required', 'date'],
            'date_fin_formation' => ['required', 'date', 'after_or_equal:date_debut_formation'],
            'fraisLignes' => ['nullable', 'array'],
            'fraisLignes.*.montant' => ['required', 'numeric', 'min:0'],
            'fraisLignes.*.date_echeance' => ['nullable', 'date'],
            'fraisLignes.*.classification' => ['nullable', Rule::in(Group::NIVEAUX)],
        ];
    }
}
