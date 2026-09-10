<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Remboursements;

use App\Domain\Finance\Support\CaisseResolver;
use App\Models\Caisse;
use App\Rules\AccessibleCaisse;
use Closure;
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
            // docs/rapports/finance/phase-10-finance-audit.md §2.6 Q1, unchanged).
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
                // ⚠ Sans `refunds.choose-till`, nommer une caisse est REFUSÉ
                // (10/09/2026) — jamais ignoré en silence. Le front-office
                // rend l'argent qu'il a physiquement en main : son propre
                // tiroir, que le contrôleur dérive. Accepter puis ignorer la
                // valeur ferait mentir l'écran (« j'ai débité la caisse de
                // Achraf ») ; refuser dit ce qui s'est passé. Une valeur
                // égale à sa PROPRE caisse passe : c'est ce que le formulaire
                // envoie quand il n'y a rien à choisir.
                function (string $attribute, mixed $value, Closure $fail): void {
                    $user = $this->user();

                    if ($user === null || $user->can('refunds.choose-till')) {
                        return;
                    }

                    $employee = $user->employee;

                    // ⚠ Passe par le MÊME CaisseResolver que le contrôleur —
                    // jamais un `till()->first()` nu. Un compte antérieur au
                    // provisionneur n'a pas encore de ligne « Caissière » :
                    // le resolver la crée, le lookup nu rendrait null et
                    // refuserait l'agent qui soumet pourtant SA propre caisse.
                    // Les deux doivent désigner le même tiroir, sinon le
                    // formulaire est refusé par ce qu'il a lui-même prérempli.
                    $till = $employee === null
                        ? null
                        : app(CaisseResolver::class)->tillOf($employee);

                    if ($till !== null && (int) $value === (int) $till->id) {
                        return;
                    }

                    $fail(__('You can only refund from your own till.'));
                },
            ],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'date_remboursement' => ['required', 'date'],
            'motif' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ];
    }
}
