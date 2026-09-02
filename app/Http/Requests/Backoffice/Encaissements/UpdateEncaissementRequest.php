<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use App\Models\Encaissement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⚠ Editing a payment is audit-logged (LogsActivity on the model).
 * `caisse_id` is deliberately NOT editable after creation — correcting it
 * must go through a remboursement + new encaissement so the money trail
 * stays intact.
 *
 * `montant` IS correctable since le 02/09/2026, but only by a SUPER-ADMIN
 * (`payments.update-amount` sits in PermissionRegistry::superAdminOnly(), so
 * no role preset holds it), and never as a bare column write: the controller
 * hands it to CorrigerMontantEncaissement, which moves the difference on the
 * payment's OWN caisse — the till of the employee who took the money, never
 * the corrector's — and journals it. Without the permission the field is
 * still accepted here so the edit modal can echo the stored value back; the
 * controller refuses a DIFFERENT value.
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
            // Borne haute volontairement absente ici : ce qui reste du sur le
            // frais depend d'autres lignes et doit etre relu SOUS VERROU dans
            // la transaction, pas au moment de la validation (CLAUDE.md §11 —
            // un controle « lire un solde puis ecrire » evalue hors
            // transaction est un double-clic qui double-depense).
            // CorrigerMontantEncaissement s'en charge.
            'montant' => ['nullable', 'numeric', 'gt:0'],
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
