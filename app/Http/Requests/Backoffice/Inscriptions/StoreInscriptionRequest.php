<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use App\Models\Student;
use App\Support\Phone\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates exactly the fields the current Livewire InscriptionsIndex form
 * exposes on CREATE (docs/phase-9-inscriptions-audit.md §4.9) — NOT the
 * wider/narrower field set the dead pre-Phase-9 version of this Request
 * used to validate (no etablissement_id/annee_scolaire_id/montant_total
 * from the client — all three are always server-derived from the group;
 * see InscriptionController::store()).
 *
 * `statut` is deliberately NOT a field here — a new registration always
 * starts "Active" server-side regardless of any submitted value, exactly
 * like InscriptionsIndex::save()'s `!$editing` branch.
 *
 * `reference`/`created_by` are system-generated — never accepted here.
 */
final class StoreInscriptionRequest extends FormRequest
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
        $rules = [
            'inscription_mode' => ['required', Rule::in(['new', 'existing'])],
            'group_id' => ['required', 'exists:groups,id'],
            'date_inscription' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'fee_lines' => ['nullable', 'array'],
            'fee_lines.*.frais_id' => ['nullable', 'integer'],
            'fee_lines.*.nom' => ['required', 'string', 'max:150'],
            'fee_lines.*.montant_initial' => ['nullable', 'numeric', 'min:0'],
            'fee_lines.*.remise_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fee_lines.*.remise_montant' => ['nullable', 'numeric', 'min:0'],
            'fee_lines.*.date_echeance' => ['nullable', 'date'],
        ];

        if ($this->input('inscription_mode') === 'existing') {
            $rules['student_id'] = ['required', 'exists:students,id'];

            return $rules;
        }

        $niveau = $this->input('new_niveau');

        return [
            ...$rules,
            'new_nom' => ['required', 'string', 'max:100'],
            'new_prenom' => ['required', 'string', 'max:100'],
            'new_sexe' => ['nullable', Rule::in(Student::SEXES)],
            'new_date_naissance' => ['nullable', 'date', 'before:today'],
            'new_cin' => ['nullable', 'string', 'max:30'],
            'new_niveau' => ['nullable', Rule::in(Student::NIVEAUX)],
            'new_domaine' => [
                Student::niveauDemandeDomaine($niveau) ? 'required' : 'nullable',
                Rule::in(Student::DOMAINES),
                Rule::excludeIf(! Student::niveauDemandeDomaine($niveau)),
            ],
            'new_examen_type' => [
                Student::niveauDemandeExamen($niveau) ? 'required' : 'nullable',
                Rule::in(Student::EXAMEN_TYPES),
                Rule::excludeIf(! Student::niveauDemandeExamen($niveau)),
            ],
            'new_email' => ['nullable', 'email', 'max:255'],
            'new_telephone' => ['nullable', 'string', 'max:20'],
            'new_whatsapp' => ['nullable', 'string', 'max:20'],
            'new_adresse' => ['nullable', 'string', 'max:255'],
            'new_parent_relation' => ['nullable', Rule::in(Student::PARENT_RELATIONS)],
            'new_parent_nom' => ['nullable', 'string', 'max:100'],
            'new_parent_sexe' => ['nullable', Rule::in(Student::SEXES)],
            'new_parent_cin' => ['nullable', 'string', 'max:30'],
            'new_parent_telephone' => ['nullable', 'string', 'max:20'],
            'new_parent_whatsapp' => ['nullable', 'string', 'max:20'],
            'phone_pays' => ['required', Rule::in(array_keys(Countries::LIST))],
        ];
    }
}
