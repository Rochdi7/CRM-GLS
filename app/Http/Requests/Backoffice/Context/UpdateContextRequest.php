<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Context;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Format-level validation only — CurrentContext::setAnneeScolaire()/
 * setEtablissement() are the actual authorization boundary (they silently
 * reject an inaccessible/invalid id; see docs/dashboard-livewire-to-inertia-map.md
 * and the existing Context test suite this preserves). This Form Request
 * exists so the controller never touches raw, unvalidated request input.
 */
final class UpdateContextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'annee_scolaire_id' => ['nullable', 'integer'],
            // null = "all centers" — a real, allowed value, not "field absent".
            'etablissement_id' => ['nullable', 'integer'],
        ];
    }
}
