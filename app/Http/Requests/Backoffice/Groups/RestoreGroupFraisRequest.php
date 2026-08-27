<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Groups;

use App\Models\Group;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * « Restaurer ce frais » on a group (GroupController@restoreFee). Blank
 * values mean "catalog default" (handled by the controller); a value that
 * is present must be a sane amount / date (audit CRUD-F7).
 */
final class RestoreGroupFraisRequest extends FormRequest
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
            'montant' => ['nullable', 'numeric', 'min:0'],
            'date_echeance' => ['nullable', 'date'],
            'classification' => ['nullable', Rule::in(Group::NIVEAUX)],
        ];
    }

    /**
     * This endpoint is called by fetch() from inside the edit modal;
     * bootstrap/app.php only renders JSON errors for `api/*`, so answer
     * JSON 422 here instead of the redirect-back the modal cannot read.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => __('The given data was invalid.'),
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
