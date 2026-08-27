<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Encaissements;

use Illuminate\Foundation\Http\FormRequest;

final class ReaffecterEncaissementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // policy + permission middleware do the real gating
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'encaissement_ids' => ['required', 'array', 'min:1'],
            'encaissement_ids.*' => ['integer', 'exists:encaissements,id'],
            // A GROUP, never one registration: the selection spans several
            // students and each one's money must land on his OWN inscription
            // in that group (see ReaffecterEncaissements).
            'group_id' => ['required', 'integer', 'exists:groups,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'encaissement_ids.required' => __('Select at least one payment to move.'),
            'group_id.required' => __('Select the group to move the payments to.'),
        ];
    }
}
