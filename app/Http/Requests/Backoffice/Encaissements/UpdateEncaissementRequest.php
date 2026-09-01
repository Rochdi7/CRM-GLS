<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use App\Models\Encaissement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⚠ Editing a payment is audit-logged (LogsActivity on the model).
 * `montant` and `caisse_id` are deliberately NOT editable after creation —
 * correcting either must go through a remboursement + new encaissement so
 * the money trail stays intact.
 *
 * `methode` IS correctable since le 01/09/2026, but only by a holder of
 * `payments.update-method` (les rôles de direction + super-admin), and never
 * as a bare column write: the controller hands it to
 * RequalifierMethodeEncaissement, which moves the money between the two
 * caisses and journals both legs. Without the permission the field is still
 * accepted here so the edit modal can echo the stored value back — the
 * controller refuses a DIFFERENT value.
 */
final class UpdateEncaissementRequest extends FormRequest
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
            'methode' => ['nullable', Rule::in(Encaissement::METHODES)],
            'date_paiement' => [
                $this->user()?->can('payments.update-date') ? 'required' : 'nullable',
                'date',
            ],
            'numero_cheque' => ['nullable', 'string', 'max:50'],
            // Free text — the Banques catalog (Paramètres → Banques) only
            // feeds the form's autocomplete suggestions, never a hard
            // constraint on what can be typed.
            'banque' => ['nullable', 'string', 'max:100'],
            'date_echeance_cheque' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
