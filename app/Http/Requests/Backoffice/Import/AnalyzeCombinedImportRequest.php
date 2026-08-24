<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Import;

use App\Models\Group;
use App\Models\Inscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Combined « Étudiants + Inscriptions » import: BOTH legacy files in one
 * flow — students are imported first, inscriptions then resolve against
 * them in the same run, so "étudiant introuvable" conflicts from running
 * the two modules separately disappear. Scope (centre + année) still comes
 * from the active context (ResolvesImportScope), and the same statut filter
 * as the standalone Inscriptions import applies.
 */
final class AnalyzeCombinedImportRequest extends FormRequest
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
            'students_file' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            // One inscriptions export per checked statut — the old CRM ships
            // Annulée and Archivée as SEPARATE files. All files are analyzed
            // into ONE batch; the statut filter still applies to every row,
            // so a file dropped in the "wrong" slot cannot smuggle rows in.
            'inscriptions_files' => ['required', 'array', 'min:1'],
            'inscriptions_files.*' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            // Honored only in « Tous les centres » mode — otherwise the
            // active context decides (ResolvesImportScope), never the client.
            'etablissement_id' => ['nullable', 'integer', 'exists:etablissements,id'],
            'statuts' => ['sometimes', 'array'],
            'statuts.*' => [Rule::in([
                Inscription::STATUT_ACTIVE,
                Inscription::STATUT_ANNULEE,
                Inscription::STATUT_CHANGEMENT,
            ])],
            // Optional third file: the payments. When present, the
            // inscriptions are committed automatically and the payments are
            // analyzed against them in the same run — with inactive
            // (Annulée / Changement) inscriptions accepted whenever those
            // statuts are part of this import, so the "cochez Accepter les
            // inscriptions annulées" conflict can no longer happen by
            // forgetting a checkbox.
            'encaissements_file' => ['nullable', 'file', 'mimes:xlsx', 'max:10240'],
            'operateur_mapping' => ['required_with:encaissements_file', 'array'],
            'operateur_mapping.*.label' => ['required', 'string'],
            'operateur_mapping.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
            'groupe_mapping' => ['present', 'array'],
            'groupe_mapping.*.label' => ['required', 'string'],
            'groupe_mapping.*.action' => ['required', Rule::in(['map', 'create'])],
            'groupe_mapping.*.group_id' => ['required_if:groupe_mapping.*.action,map', 'nullable', 'integer', 'exists:groups,id'],
            'groupe_mapping.*.nom' => ['required_if:groupe_mapping.*.action,create', 'nullable', 'string', 'max:150'],
            'groupe_mapping.*.niveau' => ['required_if:groupe_mapping.*.action,create', 'nullable', Rule::in(Group::NIVEAUX)],
        ];
    }
}
