<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Depenses;

use App\Http\Requests\Backoffice\Depenses\Concerns\PaiementProfRules;
use App\Models\Depense;
use App\Models\TypeDepense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * ⚠ `montant` and `caisse_id` are deliberately NOT editable after creation —
 * the till balance already moved; corrections need a compensating entry.
 * Edits are audit-logged (LogsActivity on the model).
 */
final class UpdateDepenseRequest extends FormRequest
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
            // Active types only — same rule as creation. The type the row
            // ALREADY carries stays acceptable even once retired, so editing
            // the note/date of a historical dépense never forces a
            // re-classification; only switching to a DIFFERENT inactive type
            // is refused.
            'type_depense_id' => [
                'required',
                Rule::exists('types_depenses', 'id')->where(function ($q): void {
                    $current = $this->route('depense')?->type_depense_id;

                    $q->where(fn ($w) => $current !== null
                        ? $w->where('statut', TypeDepense::STATUT_ACTIF)->orWhere('id', $current)
                        : $w->where('statut', TypeDepense::STATUT_ACTIF));
                }),
            ],
            // Nullable on edit: rows predating the field stay correctable.
            'methode_paiement' => ['nullable', Rule::in(Depense::METHODES)],
            'date_depense' => ['required', 'date'],
            'mots_cles' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'justificatifs.*' => ['file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ];
    }
}
