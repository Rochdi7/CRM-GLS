<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\AnneesScolaires;

use App\Http\Requests\Backoffice\AnneesScolaires\Concerns\CouvertureAnneesRules;
use App\Models\AnneeScolaire;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAnneeScolaireRequest extends FormRequest
{
    use CouvertureAnneesRules;

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
            'nom' => [
                'required', 'string', 'max:20',
                Rule::unique('annees_scolaires', 'nom')->ignore($this->route('annees_scolaire')),
            ],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
            'par_defaut' => ['sometimes', 'boolean'],
            'inscription_ouverte' => ['sometimes', 'boolean'],
            'cloturee' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $annee = $this->route('annees_scolaire');

        $validator->after(fn (Validator $v) => $this->validerCouvertureAnnees(
            $v,
            $annee instanceof AnneeScolaire ? $annee : null,
        ));
    }
}
