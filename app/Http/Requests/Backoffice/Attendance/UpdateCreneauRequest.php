<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Attendance;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateCreneauRequest extends FormRequest
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
            // group_id is deliberately not editable — changing which group a
            // créneau belongs to would orphan its already-generated séances'
            // group linkage; delete and recreate instead.
            'jour_semaine' => ['required', 'integer', 'between:1,7'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
            'enseignant_id' => ['nullable', 'exists:employees,id'],
            'salle_id' => ['nullable', 'exists:salles,id'],
        ];
    }
}
