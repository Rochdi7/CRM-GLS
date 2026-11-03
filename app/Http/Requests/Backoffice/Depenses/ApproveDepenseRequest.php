<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Depenses;

use App\Domain\Finance\Support\CaisseResolver;
use App\Models\Depense;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Approval of a pending dépense (03/11/2026).
 *
 * A TPE / Virement dépense leaves the CENTRE's account for that method
 * (CaisseResolver::forDepense), so the approver may attach the proof of the
 * payment actually sent (bank transfer screenshot, TPE slip) — stored in the
 * `preuve_paiement` media collection. OPTIONAL for now: flip
 * PREUVE_OBLIGATOIRE to make it required. An Espèces dépense has no such
 * proof, and a file sent for one is refused rather than silently dropped.
 *
 * Authorization stays in DepenseController@approve (policy + context guard).
 */
final class ApproveDepenseRequest extends FormRequest
{
    /** Owner's call 03/11/2026: optional « for now, maybe required later ». */
    public const PREUVE_OBLIGATOIRE = false;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Depense|null $depense */
        $depense = $this->route('depense');
        $compteCentre = $depense instanceof Depense
            && in_array($depense->methode_paiement, CaisseResolver::METHODES_DEPENSE_COMPTE_CENTRE, true);

        if (! $compteCentre) {
            return ['preuve_paiement' => ['prohibited']];
        }

        return [
            'preuve_paiement' => [
                self::PREUVE_OBLIGATOIRE ? 'required' : 'nullable',
                'file',
                'mimes:jpeg,jpg,png,webp,pdf',
                'max:5120',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['preuve_paiement' => __('proof of payment')];
    }
}
