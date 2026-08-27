<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates exactly the fields the current Livewire InscriptionsIndex form
 * exposes on EDIT (docs/phase-9-inscriptions-audit.md §4.7/§4.9) — only 5
 * columns are ever updated, never fees/totals/group. `date_debut`/
 * `date_fin` are taken directly from the request (NOT re-derived from the
 * group on update, unlike create — a confirmed, deliberate asymmetry
 * preserved exactly per the audit doc §12 point 2). No etablissement_id/
 * annee_scolaire_id/montant_total/group_id — never form inputs, never
 * changed on update either (group changes only ever go through
 * InscriptionController::changeGroup(), never this endpoint).
 */
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
            'date_inscription' => ['required', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'note' => ['nullable', 'string'],
        ];
    }
}
