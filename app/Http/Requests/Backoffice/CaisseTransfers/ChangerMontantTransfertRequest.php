<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\CaisseTransfers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Nouveau montant d'un transfert « En attente ».
 *
 * Même règle de forme que la création (StoreCaisseTransferRequest). Qui peut
 * le faire, sur quel statut, et jusqu'à quel plafond, est décidé par
 * ChangerMontantTransfert, sous verrou — jamais ici.
 */
final class ChangerMontantTransfertRequest extends FormRequest
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
            'montant' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
