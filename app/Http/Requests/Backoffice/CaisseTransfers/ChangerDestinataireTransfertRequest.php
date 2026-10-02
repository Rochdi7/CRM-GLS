<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\CaisseTransfers;

use App\Rules\CaisseDeServiceAccessible;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Nouveau destinataire d'un transfert « En attente ».
 *
 * La caisse est contrôlée par la MÊME règle que la création
 * (StoreCaisseTransferRequest) : un destinataire que le modal propose doit
 * être un destinataire que le serveur accepte, sinon l'écran offre ce que la
 * requête refuse. Qui peut le faire, et sur quel statut, est décidé par
 * ChangerDestinataireTransfert, sous verrou.
 */
final class ChangerDestinataireTransfertRequest extends FormRequest
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
            'caisse_destination_id' => ['required', 'integer', 'exists:caisses,id', new CaisseDeServiceAccessible],
        ];
    }
}
