<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Salles;

use App\Domain\Settings\Queries\GetAccessibleCenterOptions;
use App\Models\Salle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSalleRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:100', Rule::unique('salles', 'nom')->where('etablissement_id', $this->input('etablissement_id'))],
            // Restricted to centers the acting user may access (Phase 6 §Q3
            // fix) — not just "exists", since a forged id for an
            // inaccessible-but-real center would otherwise pass.
            'etablissement_id' => [
                'required',
                Rule::in(app(GetAccessibleCenterOptions::class)->allowedIds($this->user())),
            ],
            'capacite' => ['nullable', 'integer', 'min:1'],
            'statut' => ['required', Rule::in(Salle::STATUTS)],
        ];
    }
}
