<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Import;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'operateur_mapping.*.employee_id' => [
                'required', 'integer',
                // Bornee aux postes encaisseurs : un enseignant ne prend pas
                // l'argent d'un etudiant. Le dropdown les retire deja, mais
                // une liste cliente n'est qu'un confort (CLAUDE.md §5) — sans
                // cette regle un mapping forge repasse. Audit 09/09/2026 :
                // 3 166 paiements importes (3,1 M DH) signes par un
                // « Enseignant » ou un « Autre ».
                Rule::exists('employees', 'id')->whereNotIn('categorie', Employee::CATEGORIES_NON_ENCAISSEUSES),
            ],
            // Off by default: attaching money to a cancelled enrolment is a
            // deliberate choice the operator makes, not a silent behaviour.
            'include_inactive_inscriptions' => ['sometimes', 'boolean'],
        ];
    }
}
