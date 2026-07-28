<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use App\Models\Inscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateInscriptionRequest extends FormRequest
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
            'student_id' => ['required', 'exists:students,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'etablissement_id' => ['nullable', 'exists:etablissements,id'],
            'annee_scolaire_id' => ['nullable', 'exists:annees_scolaires,id'],
            'statut' => ['required', Rule::in(Inscription::STATUTS)],
            'date_inscription' => ['required', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'montant_total' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}
