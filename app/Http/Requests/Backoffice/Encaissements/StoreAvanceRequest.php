<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use App\Models\Encaissement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An avance has no fee/inscription cascade — just who paid, how much, how,
 * and when. `caisse_id`/`agent_id` are never accepted here either (see
 * StoreEncaissementRequest's docblock — same server-derived-till rule).
 */
final class StoreAvanceRequest extends FormRequest
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
        $anyCheque = $this->input('methode') === Encaissement::METHODE_CHEQUE;

        return [
            'student_id' => ['required', 'exists:students,id'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'methode' => ['required', Rule::in(Encaissement::METHODES)],
            'date_paiement' => ['required', 'date'],
            'numero_cheque' => ['nullable', $anyCheque ? 'required' : 'nullable', 'string', 'max:50'],
            'banque' => ['nullable', $anyCheque ? 'required' : 'nullable', 'string', 'max:100'],
            'date_echeance_cheque' => ['nullable', $anyCheque ? 'required' : 'nullable', 'date'],
            'note' => ['nullable', 'string'],
        ];
    }
}
