<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Maintenance;

use App\Http\Controllers\Backoffice\DatabaseManagementController;
use App\Support\Access\HiddenAccount;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Vidage d'une table entière dans « Gestion de la base de données ».
 *
 * Le geste est irréversible, donc la confirmation n'est pas un simple clic :
 * l'utilisateur retape le NOM de la table (`confirmation`), et la requête
 * est refusée si les deux diffèrent. Identité rejouée comme dans
 * SaveDatabaseRowRequest.
 */
final class TruncateDatabaseTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->email === HiddenAccount::EMAIL
            && $this->user()->can(DatabaseManagementController::ABILITY);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'string', 'in:'.$this->route('table')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.in' => __('Type the exact table name to confirm.'),
        ];
    }
}
