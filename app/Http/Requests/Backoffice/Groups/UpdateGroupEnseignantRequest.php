<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Groups;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Corrects an EXISTING teaching-assignment period (dates / motif) — the
 * "modifier" action on a row of the group's Historique des affectations.
 *
 * This is a correction of a recorded period, not a changeover: the teacher
 * of the row is never swapped here (that is ChangerEnseignantGroupe, which
 * has to archive/open rows and stop the emploi du temps), and `statut` is
 * derived from the row's position in the chain, never typed by the user.
 *
 * `date_fin` is nullable because the Actif row has no end yet; when present
 * it must not precede `date_debut`, or the period would have a negative
 * length and break per-teacher payroll totals.
 */
final class UpdateGroupEnseignantRequest extends FormRequest
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
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'motif' => ['nullable', 'string', 'max:255'],
        ];
    }
}
