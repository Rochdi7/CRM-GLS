<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Attendance;

use App\Models\Seance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * group_id is deliberately NOT editable: a séance with a saved roll call
 * moved to another group would carry presences of students who never
 * belonged to it. Reschedule via delete + recreate instead.
 */
final class UpdateSeanceRequest extends FormRequest
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
            'date_seance' => ['required', 'date'],
            'heure_debut' => ['nullable', 'date_format:H:i'],
            'heure_fin' => ['nullable', 'date_format:H:i', 'after:heure_debut'],
            'enseignant_id' => ['nullable', 'exists:employees,id'],
            'statut' => ['required', Rule::in(Seance::STATUTS)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
