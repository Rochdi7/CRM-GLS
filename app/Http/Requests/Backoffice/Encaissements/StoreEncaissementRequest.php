<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use App\Models\Encaissement;
use App\Models\InscriptionFee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the create-mode cascading multi-row payment form exactly as
 * EncaissementsIndex::rules() does (Phase 10,
 * docs/phase-10-finance-audit.md §2.4) — NOT the single-fee shape the
 * pre-Phase-10 version of this Request used to validate (that shape never
 * matched the live Livewire form, which has always been multi-row; see the
 * audit's §7 "Store-vs-Livewire divergence" finding).
 *
 * `reference`/`agent_id`/`caisse_id` (server-derived from the acting
 * employee's own till) are never accepted here. `montant`'s max-per-row cap
 * (remaining balance of that specific fee) can't be expressed as a static
 * rule since it depends on which fee each row targets — enforced via the
 * closure rule below, exactly mirroring the Livewire component's own
 * dynamic `max:reste` rule per touched row.
 */
final class StoreEncaissementRequest extends FormRequest
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
        $lines = $this->input('payment_lines', []);
        $anyCheque = collect($lines)->contains(fn ($l) => ($l['methode'] ?? null) === Encaissement::METHODE_CHEQUE);

        $rules = [
            'student_id' => ['required', 'exists:students,id'],
            'inscription_id' => ['required', 'exists:inscriptions,id'],
            // No caisse_id rule: the till is server-derived from the acting
            // employee's own caisse (see the class docblock) — any submitted
            // caisse_id is ignored by the controller.
            'date_paiement' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'payment_lines' => ['required', 'array', function (string $attribute, mixed $value, \Closure $fail): void {
                $hasAmount = collect($value)->contains(fn ($l) => ($l['montant'] ?? '') !== '');

                if (! $hasAmount) {
                    $fail(__('At least one payment line is required.'));
                }
            }],
            'payment_lines.*.fee_id' => ['required', 'exists:inscription_fees,id'],
            'numero_cheque' => ['nullable', $anyCheque ? 'required' : 'nullable', 'string', 'max:50'],
            // Free text — the Banques catalog (Paramètres → Banques) only
            // feeds the form's autocomplete suggestions, never a hard
            // constraint on what can be typed.
            'banque' => ['nullable', $anyCheque ? 'required' : 'nullable', 'string', 'max:100'],
            'date_echeance_cheque' => ['nullable', $anyCheque ? 'required' : 'nullable', 'date'],
        ];

        foreach ($lines as $i => $line) {
            if (($line['montant'] ?? '') === '') {
                continue;
            }

            $fee = InscriptionFee::find($line['fee_id'] ?? null);
            $reste = $fee ? round(max(0, (float) $fee->montant - $fee->montantPaye()), 2) : 0;

            $rules["payment_lines.{$i}.montant"] = ['required', 'numeric', 'min:0.01', "max:{$reste}"];
            $rules["payment_lines.{$i}.methode"] = ['required', Rule::in(Encaissement::METHODES)];
            $rules["payment_lines.{$i}.date_paiement"] = ['required', 'date'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_lines.*.montant.max' => __('The amount cannot exceed the remaining balance of this fee.'),
        ];
    }
}
