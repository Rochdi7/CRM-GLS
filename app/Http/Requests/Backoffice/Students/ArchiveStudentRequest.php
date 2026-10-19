<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Students;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Archiver un étudiant (19/10/2026) : un MOTIF et une DATE DE RETOUR
 * prévue, tous deux obligatoires — sans eux l'onglet « Étudiants
 * archivés » ne dirait ni pourquoi la personne est partie ni quand la
 * rappeler. La date est au plus tôt demain : archiver quelqu'un « de
 * retour hier » n'a pas de sens.
 */
final class ArchiveStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'max:1000'],
            'date_retour' => ['required', 'date', 'after:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motif' => __('reason'),
            'date_retour' => __('expected return date'),
        ];
    }
}
