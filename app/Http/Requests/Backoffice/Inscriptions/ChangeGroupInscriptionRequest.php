<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Inscriptions;

use App\Domain\Registrations\Actions\ChangerGroupeInscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeGroupInscriptionRequest extends FormRequest
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
            'new_group_id' => ['required', 'exists:groups,id'],
            'date_fin' => ['required', 'date'],
            'date_debut' => ['required', 'date', 'after_or_equal:date_fin'],
            'unpaid_fees_scope' => ['nullable', Rule::in(ChangerGroupeInscription::SCOPES)],
            'note' => ['nullable', 'string'],
        ];
    }
}
