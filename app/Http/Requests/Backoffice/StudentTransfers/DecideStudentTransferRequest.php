<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\StudentTransfers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Refus ou annulation d'une demande : un motif, rien d'autre. Sa
 * présence est exigée par l'action pour un REFUS (RefuserTransfertEtudiant)
 * et facultative pour une annulation par le demandeur.
 */
final class DecideStudentTransferRequest extends FormRequest
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
            'motif_decision' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
