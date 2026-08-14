<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\MotifsAnnulation;

use App\Models\MotifAnnulation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * `is_system` is NOT accepted from the form — system reasons are seeded once
 * (MotifAnnulationSeeder); the admin form only creates regular reasons.
 */
final class StoreMotifAnnulationRequest extends FormRequest
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
            'nom' => ['required', 'string', 'max:150', 'unique:motifs_annulation,nom'],
            'statut' => ['required', Rule::in(MotifAnnulation::STATUTS)],
        ];
    }
}
