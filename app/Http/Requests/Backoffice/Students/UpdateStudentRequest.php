<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Students;

use App\Models\Student;
use App\Support\Phone\Countries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateStudentRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'sexe' => ['nullable', Rule::in(Student::SEXES)],
            'date_naissance' => ['nullable', 'date', 'before:today'],
            'phone_pays' => ['required', Rule::in(array_keys(Countries::LIST))],
            'telephone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'cin' => ['nullable', 'string', 'max:30'],
            'niveau' => ['nullable', Rule::in(Student::NIVEAUX)],
            // Orientation: required by — and only accepted for — its own track.
            'domaine' => [
                Student::niveauDemandeDomaine($this->input('niveau')) ? 'required' : 'nullable',
                Rule::in(Student::DOMAINES),
                Rule::excludeIf(! Student::niveauDemandeDomaine($this->input('niveau'))),
            ],
            'examen_type' => [
                Student::niveauDemandeExamen($this->input('niveau')) ? 'required' : 'nullable',
                Rule::in(Student::EXAMEN_TYPES),
                Rule::excludeIf(! Student::niveauDemandeExamen($this->input('niveau'))),
            ],
            'etablissement_id' => ['nullable', 'exists:etablissements,id'],
            'parent_nom' => ['nullable', 'string', 'max:100'],
            'parent_relation' => ['nullable', Rule::in(Student::PARENT_RELATIONS)],
            'parent_sexe' => ['nullable', Rule::in(Student::SEXES)],
            'parent_cin' => ['nullable', 'string', 'max:30'],
            'parent_telephone' => ['nullable', 'string', 'max:20'],
            'parent_whatsapp' => ['nullable', 'string', 'max:20'],
            'note' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
