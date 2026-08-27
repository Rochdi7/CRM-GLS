<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Groups;

use App\Models\Group;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates exactly the fields the current Livewire GroupsIndex form exposes
 * (docs/phase-8-students-groups-inventory.md) — no salle_id/capacite_max,
 * which do not appear anywhere in the live UI. etablissement_id/
 * annee_scolaire_id are never form inputs: the controller always inherits
 * them from CurrentContext, matching GroupsIndex::save().
 */
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
            // A new group always starts at the beginning of its lifecycle.
            'statut' => ['required', Rule::in([Group::STATUT_EN_INSCRIPTION, Group::STATUT_EN_FORMATION])],
            'date_debut_formation' => ['required', 'date'],
            'date_fin_formation' => ['required', 'date', 'after_or_equal:date_debut_formation'],
            'fraisLignes' => ['nullable', 'array'],
            'fraisLignes.*.montant' => ['required', 'numeric', 'min:0'],
            'fraisLignes.*.date_echeance' => ['nullable', 'date'],
            'fraisLignes.*.classification' => ['nullable', Rule::in(Group::NIVEAUX)],
            // Catalog fees the user took OUT of the new group with the trash
            // icon before saving — same gesture as the edit modal's
            // removeFee(), but purely client-side here since nothing exists
            // yet to detach from.
            'fraisRetires' => ['nullable', 'array'],
            'fraisRetires.*' => ['integer', 'exists:frais,id'],
        ];
    }
}
