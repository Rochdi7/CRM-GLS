<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Remboursements;

use Illuminate\Foundation\Http\FormRequest;

/**
 * `reference` is system-generated and `agent_id`/`caisse_id` come from the
 * authenticated employee's own till (RemboursementController::store()) —
 * none of the three is accepted from the request, matching
 * EncaissementController's same server-derived-till rule.
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
            'montant' => ['required', 'numeric', 'min:0.01'],
            'date_remboursement' => ['required', 'date'],
            'motif' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];
    }
}
