<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Attendance;

use App\Models\Presence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Roll-call payload: `presences` keyed by student_id. Enrollment membership
 * is re-verified server-side in EnregistrerPresences — validation here only
 * guarantees shape and statut values.
 */
final class SavePresencesRequest extends FormRequest
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
            'presences' => ['required', 'array', 'min:1'],
            'presences.*.statut' => ['required', Rule::in(Presence::STATUTS)],
            'presences.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
