<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Remboursements;

use App\Models\Caisse;
use App\Rules\AccessibleCaisse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `reference` and `agent_id` stay system-derived. `caisse_id` is now CHOSEN
 * (03/09/2026): deriving it from the acting employee's own till meant a
 * cashier refunding another centre's student silently drained a till homed
 * elsewhere, with the row then invisible on both centres. It is validated
 * against centre reach (AccessibleCaisse) and restricted to CASH accounts —
 * a refund never comes out of a TPE/Chèque/Virement account (CLAUDE.md §11).
 */
final class StoreRemboursementRequest extends FormRequest
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
            'beneficiaire_id' => ['required', 'exists:students,id'],
            // Optional: picking one of the student's listed payments links
            // the refund back to it for traceability, but a refund unrelated
            // to any tracked payment is still allowed (no max-amount check —
            // docs/phase-10-finance-audit.md §2.6 Q1, unchanged).
            'encaissement_id' => ['nullable', 'exists:encaissements,id'],
            // Cash accounts only, and only in a centre the user can reach.
            // The bounced-cheque exception stays server-side
            // (CaisseResolver::forRemboursement) and overrides this.
            'caisse_id' => [
                // NULLABLE, deliberately. A missing caisse_id falls back to
                // the acting employee's own till (the pre-03/09/2026
                // behaviour) — making it `required` would resurrect the
                // "click Enregistrer, nothing happens" bug for any caller
                // that does not send it, which is what
                // test_a_remboursement_can_be_created_with_no_caisse_id_in_the_payload
                // guards. The form always sends it; the fallback is the net.
                'nullable',
                Rule::exists('caisses', 'id')->whereIn('type', Caisse::TYPES_ESPECES),
                new AccessibleCaisse(),
            ],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'date_remboursement' => ['required', 'date'],
            'motif' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];
    }
}
