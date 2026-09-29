<?php

declare(strict_types=1);

namespace App\Http\Requests\Backoffice\Employees\Concerns;

use App\Models\Employee;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            // Le doublon se juge sur le couple (groupe, mois) — voir
            // paiementProfDoublons() : un même mois peut porter un montant
            // général ET un montant par groupe (29/09/2026).
            'taux_mensuels.*.mois' => ['required', 'date_format:Y-m'],
            // Vide = « Tous les groupes ». Le groupe doit être l'un de CEUX
            // de l'enseignant (affectation courante ou passée) : un montant
            // posé sur le groupe d'un collègue ne serait jamais lu.
            'taux_mensuels.*.group_id' => [
                'nullable', 'integer',
                Rule::exists('groups', 'id')->where(function ($q): void {
                    $employeeId = $this->route('employee')?->id;

                    $q->where(fn ($w) => $w
                        ->where('enseignant_id', $employeeId ?? 0)
                        ->orWhereIn('id', fn ($s) => $s->select('group_id')->from('group_enseignants')
                            ->where('enseignant_id', $employeeId ?? 0)));
                }),
            ],
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
            'taux_mensuels.*.group_id.exists' => __('This group is not one of this teacher’s groups.'),
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->paiementProfDoublons() as $champ => $message) {
                    $validator->errors()->add($champ, $message);
                }
            },
        ];
    }

    /**
     * Un même (groupe, mois) saisi deux fois : la seconde ligne est refusée
     * sur son champ « mois », pour que l'écran la désigne.
     *
     * @return array<string, string>
     */
    protected function paiementProfDoublons(): array
    {
        if ($this->input('mode_paiement_prof') !== Employee::MODE_PAIEMENT_WIN_WIN) {
            return [];
        }

        $vus = [];
        $erreurs = [];

        foreach ((array) $this->input('taux_mensuels', []) as $index => $ligne) {
            $cle = ($ligne['group_id'] ?? '') === '' || ($ligne['group_id'] ?? null) === null
                ? 'tous|'.($ligne['mois'] ?? '')
                : (int) $ligne['group_id'].'|'.($ligne['mois'] ?? '');

            if (isset($vus[$cle])) {
                $erreurs["taux_mensuels.{$index}.mois"] = __('The same month is entered twice for the same group.');
            }

            $vus[$cle] = true;
        }

        return $erreurs;
    }
}
