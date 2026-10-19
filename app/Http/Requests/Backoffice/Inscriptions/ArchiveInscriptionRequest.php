<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Archiver une inscription (19/10/2026) : un MOTIF et une DATE DE RETOUR
 * prévue, tous deux obligatoires — sans eux l'onglet « Inscriptions
 * archivées » ne dirait ni pourquoi le dossier est en pause ni quand
 * rappeler l'étudiant. La date est au plus tôt demain.
 */
final class ArchiveInscriptionRequest extends FormRequest
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
