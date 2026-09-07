<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de « Échéances en masse ».
 *
 * `exists:` prouve seulement que la ligne existe — la PORTÉE (centres
 * affectés + contexte actif) est vérifiée par
 * ModifierEcheancesFraisEnMasse, qui refuse le lot entier si un id lui
 * échappe. La règle est volontairement laissée à l'action : elle est le
 * seul chemin d'écriture, donc un futur appelant non-HTTP hérite du même
 * contrôle (H-10 de l'audit du 07/09/2026 : « les dropdowns sont
 * restreints, les chemins d'écriture non »).
 */
final class BulkUpdateFeeDueDatesRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'fee_ids' => ['required', 'array', 'min:1'],
            'fee_ids.*' => ['integer', 'exists:inscription_fees,id'],
            'date_echeance' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'fee_ids' => __('lines'),
            'date_echeance' => __('due date'),
        ];
    }
}
