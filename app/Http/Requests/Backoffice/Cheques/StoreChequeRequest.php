<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Cheques;

use App\Models\Cheque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Ajouter un chèque" — source drives the owner shape: Étudiant requires a
 * student_id (searchable picker), Parents requires a proprietaire_nom
 * picked from a student's parent on file instead. Banque is a name string
 * suggested from the banques catalog (same free-text convention as
 * encaissements.banque).
 */
final class StoreChequeRequest extends FormRequest
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
            'source' => ['required', Rule::in(Cheque::SOURCES)],
            'student_id' => [
                Rule::requiredIf($this->input('source') === Cheque::SOURCE_ETUDIANT),
                'nullable', 'exists:students,id',
            ],
            'proprietaire_nom' => [
                Rule::requiredIf($this->input('source') === Cheque::SOURCE_PARENTS),
                'nullable', 'string', 'max:150',
            ],
            'numero_cheque' => ['required', 'string', 'max:50'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'banque' => ['nullable', 'string', 'max:100'],
            'date_reception' => ['required', 'date'],
            'type' => ['required', Rule::in(Cheque::TYPES)],
            'date_echeance' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
