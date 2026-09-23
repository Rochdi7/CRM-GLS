<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Maintenance;

use App\Http\Controllers\Backoffice\MovePaymentController;
use App\Support\Access\HiddenAccount;
use Illuminate\Foundation\Http\FormRequest;

/**
 * « Déplacer un paiement » — l'écriture. Identité rejouée comme dans les
 * Form Requests de « Gestion de la base de données » : le middleware
 * `can:` décide, le contrôleur revérifie, la requête rejoue (§16).
 *
 * Le motif est OBLIGATOIRE : c'est ce que le journal conserve pour
 * expliquer, des mois plus tard, pourquoi l'argent d'un étudiant est
 * passé au nom d'un autre.
 */
final class MovePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->email === HiddenAccount::EMAIL
            && $this->user()->can(MovePaymentController::ABILITY);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'encaissement' => ['required', 'string', 'max:30', 'exists:encaissements,reference'],
            'inscription' => ['required', 'string', 'max:30', 'exists:inscriptions,reference'],
            'inscription_fee_id' => ['nullable', 'integer', 'exists:inscription_fees,id'],
            'purger_presences' => ['sometimes', 'boolean'],
            'motif' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'encaissement' => __('Payment reference'),
            'inscription' => __('Target registration reference'),
            'inscription_fee_id' => __('Target fee'),
            'motif' => __('Reason'),
        ];
    }
}
