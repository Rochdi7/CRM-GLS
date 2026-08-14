<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Cheques;

use App\Models\Cheque;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edit modal for an existing chèque. montant stays editable while the
 * chèque is unused; once any Encaissement is linked (montant utilisé > 0)
 * the controller refuses lowering it below the used amount. `statut` is
 * NOT accepted here — lifecycle moves only through the dedicated
 * updateStatut endpoint (Remise à la banque / Encaissé / Rejeté).
 */
final class UpdateChequeRequest extends FormRequest
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
