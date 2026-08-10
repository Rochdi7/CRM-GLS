<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Attendance;

use Illuminate\Foundation\Http\FormRequest;

final class StoreCreneauRequest extends FormRequest
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
            'group_id' => ['required', 'exists:groups,id'],
            // One créneau is created per selected day — same time/teacher/room
            // for each (the "select Lundi/Mardi/Mercredi at once" UX).
            'jours_semaine' => ['required', 'array', 'min:1'],
            'jours_semaine.*' => ['integer', 'between:1,7', 'distinct'],
            'heure_debut' => ['required', 'date_format:H:i'],
            'heure_fin' => ['required', 'date_format:H:i', 'after:heure_debut'],
            'enseignant_id' => ['nullable', 'exists:employees,id'],
            'salle_id' => ['nullable', 'exists:salles,id'],
        ];
    }
}
