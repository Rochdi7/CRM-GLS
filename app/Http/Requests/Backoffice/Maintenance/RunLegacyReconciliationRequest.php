<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Maintenance;

use App\Support\Access\HiddenAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validation de « Réconciliation des paiements importés ».
 *
 * ⚠ `dossier` désigne un chemin sur le SERVEUR : sans contrainte, le champ
 * laisserait pointer l'outil n'importe où sur le disque. Il est donc
 * validé contre le seul dossier configuré (`gls.legacy_import_path`) et ses
 * sous-dossiers — le champ reste modifiable pour couvrir une arborescence
 * d'export déplacée, jamais pour sortir de la racine autorisée.
 *
 * L'autorisation elle-même est une IDENTITÉ (le compte de maintenance), pas
 * une permission : elle est rejouée ici en plus du contrôleur et du
 * middleware de route, parce qu'un Form Request qui autorise tout le monde
 * est un trou silencieux le jour où la route change.
 */
final class RunLegacyReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->email === HiddenAccount::EMAIL
            && $this->user()->can('legacy-payments.reconcile');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'dossier' => ['required', 'string', 'max:255'],
            'centre' => ['nullable', 'string', 'max:100'],
            'etudiant' => ['nullable', 'string', 'max:100'],
            'apply' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $dossier = (string) $this->input('dossier');

            if ($dossier === '') {
                return;
            }

            $racine = realpath((string) config('gls.legacy_import_path', base_path('data')));
            $cible = realpath($dossier);

            if ($racine === false) {
                $v->errors()->add('dossier', __('The configured legacy import folder does not exist on this server.'));

                return;
            }

            if ($cible === false) {
                $v->errors()->add('dossier', __('This folder does not exist on the server.'));

                return;
            }

            // Le chemin doit rester DANS la racine configurée : un
            // « ../../etc » resolu par realpath() sortirait sinon du
            // périmètre prévu.
            if ($cible !== $racine && ! str_starts_with($cible, $racine.DIRECTORY_SEPARATOR)) {
                $v->errors()->add('dossier', __('This folder is outside the configured legacy import folder.'));
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'dossier' => __('folder'),
            'centre' => __('centre'),
            'etudiant' => __('student'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'apply' => $this->boolean('apply'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        /** @var array<string, mixed> $data */
        $data = parent::validated();

        return [
            'dossier' => (string) $data['dossier'],
            'centre' => (string) ($data['centre'] ?? ''),
            'etudiant' => (string) ($data['etudiant'] ?? ''),
            'apply' => (bool) ($data['apply'] ?? false),
        ];
    }
}
