<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Groups;

use Illuminate\Foundation\Http\FormRequest;

/**
 * "Changer d'enseignant" — the explicit teacher-changeover form on the group
 * detail page. `date_debut` is the changeover date: it closes the outgoing
 * teacher's assignment AND their emploi du temps, and opens the incoming
 * teacher's period (see Domain\Groups\Actions\ChangerEnseignantGroupe).
 *
 * enseignant_id is nullable on purpose — clearing it simply ends the current
 * assignment and leaves the group without a teacher.
 */
final class ChangerEnseignantRequest extends FormRequest
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
            'enseignant_id' => ['nullable', 'exists:employees,id'],
            'date_debut' => ['required', 'date'],
            'motif' => ['nullable', 'string', 'max:500'],
        ];
    }
}
