<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Employees\Concerns;

use App\Models\Employee;
use Illuminate\Validation\Rule;

/**
 * Règles de l'onglet « Paiement prof » de la fiche employé (22/09/2026),
 * partagées par Store/UpdateEmployeeRequest pour que les deux ne divergent
 * jamais.
 *
 * Le contrat suit le MODE soumis :
 *
 * | champ                         | horaire  | gls      | win_win            |
 * |-------------------------------|----------|----------|--------------------|
 * | taux_horaire_prof             | requis   | ignoré   | ignoré             |
 * | montant_par_etudiant_prof     | ignoré   | requis   | ignoré             |
 * | taux_mensuels[] (mois+montant)| ignoré   | ignoré   | ≥ 1 ligne          |
 *
 * Tout est facultatif tant qu'aucun mode n'est choisi — un employé qui
 * n'enseigne pas n'a rien à remplir ici. Un mode sans son taux est refusé
 * (422, message nommé) : accepter puis calculer sur 0 ferait payer un prof à
 * zéro sans que rien ne l'explique.
 */
trait PaiementProfEnseignantRules
{
    /** @return array<string, mixed> */
    protected function paiementProfRules(): array
    {
        $mode = $this->input('mode_paiement_prof');

        return [
            'mode_paiement_prof' => ['nullable', Rule::in(Employee::MODES_PAIEMENT_PROF)],
            'taux_horaire_prof' => [
                Rule::requiredIf($mode === Employee::MODE_PAIEMENT_HORAIRE),
                'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'montant_par_etudiant_prof' => [
                Rule::requiredIf($mode === Employee::MODE_PAIEMENT_GLS),
                'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'taux_mensuels' => [
                Rule::requiredIf($mode === Employee::MODE_PAIEMENT_WIN_WIN),
                'nullable', 'array',
            ],
            // « YYYY-MM » : le 1er du mois est posé côté serveur, l'écran
            // n'a pas à choisir un jour.
            'taux_mensuels.*.mois' => ['required', 'date_format:Y-m', 'distinct'],
            'taux_mensuels.*.montant_par_etudiant' => ['required', 'numeric', 'min:0', 'max:999999'],
        ];
    }

    /** @return array<string, string> */
    protected function paiementProfMessages(): array
    {
        return [
            'taux_horaire_prof.required' => __('An hourly rate is required for the « Par heure » mode.'),
            'montant_par_etudiant_prof.required' => __('An amount per student is required for the « Système GLS » mode.'),
            'taux_mensuels.required' => __('Enter at least one month for the « Win-win » mode.'),
            'taux_mensuels.*.mois.distinct' => __('The same month is entered twice.'),
        ];
    }
}
