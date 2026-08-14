<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\MotifsAnnulation;

use App\Models\MotifAnnulation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * System reasons (is_system = true) are protected — the controller rejects
 * the mutation before validation even matters (403).
 */
final class UpdateMotifAnnulationRequest extends FormRequest
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
            'nom' => [
                'required', 'string', 'max:150',
                Rule::unique('motifs_annulation', 'nom')->ignore($this->route('motifAnnulation')),
            ],
            'statut' => ['required', Rule::in(MotifAnnulation::STATUTS)],
        ];
    }
}
