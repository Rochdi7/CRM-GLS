<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use App\Domain\Registrations\Actions\AnnulerInscription;
use App\Domain\Settings\Queries\GetMotifsAnnulationList;
use App\Models\MotifAnnulation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * "Annuler l'inscription". The reason is REQUIRED and must be one of the
 * ACTIVE names in the MotifAnnulation catalog — the same validate-the-name,
 * store-the-text arrangement Seance uses, so a reason deactivated later
 * never invalidates or rewrites a cancellation already recorded.
 *
 * « Changement de groupe » is excluded on purpose: it is the system reason
 * written by ChangerGroupeInscription, which archives the enrollment and
 * creates a successor. Picking it here would claim a group change that
 * never happened.
 */
final class CancelInscriptionRequest extends FormRequest
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
        $motifs = array_values(array_filter(
            app(GetMotifsAnnulationList::class)->activeNames(MotifAnnulation::PORTEE_INSCRIPTION),
            fn (string $nom): bool => $nom !== MotifAnnulation::MOTIF_CHANGEMENT_GROUPE,
        ));

        return [
            'motif_annulation' => ['required', 'string', Rule::in($motifs)],
            'date_fin' => ['required', 'date'],
            'unpaid_fees_scope' => ['nullable', Rule::in(AnnulerInscription::SCOPES)],
            'note' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motif_annulation' => __('cancellation reason'),
            'date_fin' => __('end date'),
        ];
    }
}
