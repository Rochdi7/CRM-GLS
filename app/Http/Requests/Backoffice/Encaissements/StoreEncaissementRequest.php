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
 *
 * A row paying by Chèque MUST reference an existing tracked Cheque
 * (Chèques module) via `payment_lines.*.cheque_id` — there is no manual
 * numéro/banque/échéance entry anymore; that data always comes from the
 * Cheque row (EncaissementController@store).
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
        ];

        foreach ($lines as $i => $line) {
            if (($line['montant'] ?? '') === '') {
                continue;
            }

            $fee = InscriptionFee::find($line['fee_id'] ?? null);
            $reste = $fee ? round(max(0, (float) $fee->montant - $fee->montantPaye()), 2) : 0;
            $isCheque = ($line['methode'] ?? null) === Encaissement::METHODE_CHEQUE;

            $rules["payment_lines.{$i}.montant"] = ['required', 'numeric', 'min:0.01', "max:{$reste}"];
            $rules["payment_lines.{$i}.methode"] = ['required', Rule::in(Encaissement::METHODES)];
            $rules["payment_lines.{$i}.date_paiement"] = ['required', 'date'];
            $rules["payment_lines.{$i}.cheque_id"] = [$isCheque ? 'required' : 'nullable', 'exists:cheques,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_lines.*.montant.max' => __('The amount cannot exceed the remaining balance of this fee.'),
            'payment_lines.*.cheque_id.required' => __('Select a recorded cheque to pay with.'),
        ];
    }
}
