<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Students;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Fusion de deux fiches étudiant (super-admin, `students.merge`).
 *
 * L'autorisation réelle est refaite dans le contrôleur et la policy ; ici on
 * ne valide que la forme de la paire. Le refus « même fiche des deux côtés »
 * est répété dans FusionnerEtudiants (appelant non-HTTP possible).
 */
final class FusionnerEtudiantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('merge', Student::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'garde_id' => ['required', 'integer', 'exists:students,id'],
            'doublon_id' => ['required', 'integer', 'exists:students,id', 'different:garde_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'doublon_id.different' => __('A student cannot be merged with themselves.'),
        ];
    }
}
