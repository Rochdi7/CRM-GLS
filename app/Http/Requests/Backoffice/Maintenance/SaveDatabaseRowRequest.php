<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Maintenance;

use App\Http\Controllers\Backoffice\DatabaseManagementController;
use App\Support\Access\HiddenAccount;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Ajout (POST) ou modification (PUT) d'une ligne dans « Gestion de la base
 * de données ».
 *
 * `values` porte les colonnes saisies, `key` (modification seulement) les
 * valeurs de clé primaire de la ligne visée — toujours dans le corps, jamais
 * dans l'URL, car une clé composite ou textuelle n'y tient pas. La
 * conformité des colonnes au schéma est vérifiée par DatabaseBrowser, qui
 * ignore tout nom inconnu ; PostgreSQL tranche le reste (types, contraintes)
 * et son refus remonte tel quel dans le modal.
 *
 * L'autorisation est une IDENTITÉ (le compte de maintenance), rejouée ici
 * en plus du gate de route et du contrôleur (§16).
 */
final class SaveDatabaseRowRequest extends FormRequest
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
        $rules = [
            'values' => ['required', 'array'],
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['key'] = ['required', 'array', 'min:1'];
        }

        return $rules;
    }

    /**
     * @return array{values: array<string, mixed>, key: array<string, mixed>}
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array<string, mixed> $data */
        $data = parent::validated();

        return [
            'values' => (array) ($data['values'] ?? []),
            'key' => (array) ($data['key'] ?? []),
        ];
    }
}
