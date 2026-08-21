<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Caisses;

use App\Domain\Finance\Queries\GetComptesCaisse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating an account from the « Comptes de caisse » tab.
 *
 * ⚠ "Externe" is the ONLY creatable type (GetComptesCaisse::CREATABLE_TYPES):
 *  - "Caissière" belongs to CaisseProvisioner (EmployeeObserver) so exactly
 *    one till exists per employee — a hand-made second one would silently
 *    split their balance.
 *  - TPE / Chèque / Virement are payment methods, not rows: their totals are
 *    aggregated live from the movements carrying them, so creating a row for
 *    one would make a second, drifting copy of the same money.
 *
 * The form is deliberately just Type + Nom. An Externe account opens at 0,00
 * DH and Active — there is no opening-balance field, so a balance is NEVER
 * typed by hand anywhere in the app; it only ever moves through CaisseLedger,
 * which keeps every movement in the audit journal.
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
            'type' => ['required', Rule::in(GetComptesCaisse::CREATABLE_TYPES)],
            'etablissement_id' => ['nullable', 'exists:etablissements,id'],
        ];
    }
}
