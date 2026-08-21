<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Caisses;

use App\Models\Caisse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⚠ `solde` is deliberately NOT accepted here — a till balance must never be
 * edited by hand (fraud traceability, schema §10). It only moves through
 * encaissements / depenses / remboursements / validated caisse_transfers, and
 * always via CaisseLedger so the movement lands in the audit journal.
 *
 * ⚠ `type` is NOT accepted either: an account's kind is frozen at creation.
 * Flipping an employee's "Caissière" till to "Banque" (or the reverse) would
 * rewrite the meaning of every movement already attached to it.
 */
final class UpdateCaisseRequest extends FormRequest
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
            'statut' => ['required', Rule::in(Caisse::STATUTS)],
        ];
    }
}
