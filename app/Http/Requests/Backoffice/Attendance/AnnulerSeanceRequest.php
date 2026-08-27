<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Attendance;

use App\Domain\Settings\Queries\GetMotifsAnnulationList;
use App\Models\MotifAnnulation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Annuler la séance". The reason is REQUIRED and must be one of the ACTIVE
 * names in the MotifAnnulation catalog (Paramètres → Raisons d'annulation ou
 * archivage) — the same validate-the-name, store-the-text arrangement
 * CancelInscriptionRequest uses, so a reason deactivated later never
 * invalidates or rewrites a cancellation already recorded.
 *
 * « Changement de groupe » is excluded on purpose: it is the system reason
 * ChangerGroupeInscription writes when it archives an enrollment, and it says
 * nothing about why a class session did not take place.
 */
final class AnnulerSeanceRequest extends FormRequest
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
            'motif' => ['required', 'string', Rule::in(self::motifs())],
        ];
    }

    /**
     * Active reason names offered by this form — shared with the controller so
     * the dropdown and the validation rule can never drift apart.
     *
     * @return list<string>
     */
    public static function motifs(): array
    {
        return array_values(array_filter(
            app(GetMotifsAnnulationList::class)->activeNames(),
            fn (string $nom): bool => $nom !== MotifAnnulation::MOTIF_CHANGEMENT_GROUPE,
        ));
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motif' => __('cancellation reason'),
        ];
    }
}
