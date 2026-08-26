<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Depenses;

use App\Http\Requests\Backoffice\Depenses\Concerns\PaiementProfRules;
use App\Models\Depense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `reference` is system-generated and `agent_id`/`caisse_id` come from the
 * authenticated employee's own till (DepenseController::store()) — none of
 * the three is accepted from the request.
 *
 * `justificatifs.*` mirrors Depense::registerMediaCollections()'s mime
 * allowlist and DepensesIndex's own file-rule contract exactly (Phase 10 —
 * the pre-Phase-10 version of this Request didn't declare this field at
 * all, since the Livewire component never routed through it).
 */
final class StoreDepenseRequest extends FormRequest
{
    use PaiementProfRules;

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
            ...$this->typeDependentRules(),
            'type_depense_id' => ['required', 'exists:types_depenses,id'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'methode_paiement' => ['required', Rule::in(Depense::METHODES)],
            'date_depense' => ['required', 'date'],
            'mots_cles' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'justificatifs.*' => ['file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ];
    }
}
