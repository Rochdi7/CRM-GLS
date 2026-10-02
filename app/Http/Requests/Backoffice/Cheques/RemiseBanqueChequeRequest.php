<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Cheques;

use App\Models\Cheque;
use Illuminate\Foundation\Http\FormRequest;

/**
 * « Remise à la banque » (30/09/2026) — date de remise et reçu de dépôt
 * OBLIGATOIRE. Le compte bancaire est choisi par le comptable à la
 * validation (ValiderRemiseCheque), pas ici.
 */
final class RemiseBanqueChequeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_remise' => ['required', 'date', 'before_or_equal:today'],
            'justificatif' => ['required', 'file', 'mimes:'.implode(',', Cheque::MEDIA_MIMES), 'max:'.Cheque::MEDIA_MAX_KB],
        ];
    }

    public function attributes(): array
    {
        return [
            'date_remise' => __('deposit date'),
            'justificatif' => __('deposit receipt'),
        ];
    }
}
