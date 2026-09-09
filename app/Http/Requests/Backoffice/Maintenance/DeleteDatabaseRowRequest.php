<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Maintenance;

use App\Http\Controllers\Backoffice\DatabaseManagementController;
use App\Support\Access\HiddenAccount;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Suppression d'une ligne dans « Gestion de la base de données » — la ligne
 * est désignée par ses valeurs de clé primaire (`key`). Identité rejouée
 * comme dans SaveDatabaseRowRequest.
 */
final class DeleteDatabaseRowRequest extends FormRequest
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
            'key' => ['required', 'array', 'min:1'],
        ];
    }

    /**
     * @return array{key: array<string, mixed>}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array<string, mixed> $data */
        $data = parent::validated();

        return ['key' => (array) ($data['key'] ?? [])];
    }
}
