<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\StudentTransfers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Demande de transfert d'un étudiant vers un autre centre. Le groupe
 * d'affectation dans le centre cible est OBLIGATOIRE (demande métier du
 * 25/09/2026) ; son appartenance au centre cible et son statut sont
 * revérifiés par DemanderTransfertEtudiant.
 */
final class StoreStudentTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('student-transfers.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'etablissement_cible_id' => ['required', 'integer', 'exists:etablissements,id'],
            'group_cible_id' => ['required', 'integer', 'exists:groups,id'],
            'motif' => ['required', 'string', 'max:1000'],
            // Les paiements cochés (14/10/2026). Présent mais VIDE = rien ne
            // part ; absent = sélection par défaut. L'appartenance à
            // l'étudiant et le droit de partir sont décidés par
            // SelectionPaiementsTransfert, jamais ici.
            'encaissement_ids' => ['sometimes', 'array'],
            'encaissement_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => __('student'),
            'etablissement_cible_id' => __('target center'),
            'group_cible_id' => __('target group'),
            'motif' => __('reason'),
            'encaissement_ids' => __('payments to transfer'),
        ];
    }
}
