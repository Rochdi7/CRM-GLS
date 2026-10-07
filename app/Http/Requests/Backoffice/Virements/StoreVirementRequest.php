<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Virements;

use App\Models\Virement;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Demande de virement (07/10/2026) — déclarée depuis le modal « Enregistrer
 * un paiement » quand la méthode choisie est « Virement ».
 *
 * Le plafond du montant (reste dû du frais moins les virements déjà en
 * attente) dépend du frais visé : il est contrôlé sous verrou par
 * DemanderVirement, pas par une règle statique ici.
 */
final class StoreVirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'inscription_id' => ['required', 'integer', 'exists:inscriptions,id'],
            'fee_id' => ['required', 'integer', 'exists:inscription_fees,id'],
            'montant' => ['required', 'numeric', 'min:0.01'],
            'nom_payeur' => ['required', 'string', 'max:150'],
            'reference_virement' => ['required', 'string', 'max:100'],
            // Super-admin seul (`payments.update-date`) : pour les autres la
            // date est forcée au jour même par VirementController@store.
            'date_operation' => [
                $this->user()?->can('payments.update-date') ? 'required' : 'nullable',
                'date',
                'before_or_equal:today',
            ],
            'note' => ['nullable', 'string'],
            'justificatif' => ['required', 'file', 'mimes:'.implode(',', Virement::MEDIA_MIMES), 'max:'.Virement::MEDIA_MAX_KB],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_id' => __('student'),
            'inscription_id' => __('registration'),
            'fee_id' => __('fee'),
            'montant' => __('amount'),
            'nom_payeur' => __('payer name'),
            'reference_virement' => __('bank transfer reference'),
            'date_operation' => __('operation date'),
            'justificatif' => __('transfer proof'),
        ];
    }
}
