<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Caisses;

use App\Models\Caisse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `solde` is accepted ONLY at creation (opening balance). After that the
 * balance moves exclusively through encaissements / depenses / remboursements
 * / validated transfers — see UpdateCaisseRequest.
 */
final class StoreCaisseRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:100'],
            'etablissement_id' => ['nullable', 'exists:etablissements,id'],
            'responsable_employee_id' => ['nullable', 'exists:employees,id'],
            'solde' => ['nullable', 'numeric', 'min:0'],
            'statut' => ['required', Rule::in(Caisse::STATUTS)],
        ];
    }
}
