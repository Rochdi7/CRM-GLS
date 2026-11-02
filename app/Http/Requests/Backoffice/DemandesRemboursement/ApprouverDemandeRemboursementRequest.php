<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\DemandesRemboursement;

use App\Models\DemandeRemboursement;
use App\Models\Encaissement;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Approbation d'une demande de remboursement (02/11/2026). `methode` vaut
 * Espèces par défaut ; TPE / Virement exige `refunds.choose-method`
 * (super-admin). Une autre méthode soumise sans ce droit est REFUSÉE (422),
 * jamais ramenée à Espèces en silence. ApprouverDemandeRemboursement rejoue
 * la même règle sous verrou.
 */
final class ApprouverDemandeRemboursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('methode')) {
            $this->merge(['methode' => Encaissement::METHODE_ESPECES]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'methode' => [
                'required',
                'string',
                Rule::in(DemandeRemboursement::METHODES),
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ($value !== Encaissement::METHODE_ESPECES && ! $this->user()?->can('refunds.choose-method')) {
                        $fail(__('Only a super-admin can approve a refund by card or bank transfer.'));
                    }
                },
            ],
        ];
    }

    public function attributes(): array
    {
        return ['methode' => __('method')];
    }
}
