<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Support;

use App\Models\Employee;
use Illuminate\Support\Carbon;

/**
 * Résout, pour UN enseignant et UN mois, ce qui sert au calcul : le mode et
 * le taux.
 *
 * C'est la SEULE lecture de `employees.mode_paiement_prof` /
 * `taux_horaire_prof` / `montant_par_etudiant_prof` et de
 * `enseignant_taux_mensuels` côté paie. L'écran de calcul, le contrôleur et
 * les tests passent tous par ici, sinon le mode affiché finirait par
 * diverger du mode calculé.
 *
 * Trois refus, chacun NOMMÉ (§11 « signaler plutôt que masquer ») :
 *  - pas de mode configuré sur la fiche ;
 *  - mode `gls` / `horaire` sans son taux ;
 *  - mode `win_win` sans ligne pour le MOIS demandé — on ne prend jamais le
 *    mois voisin ni le taux GLS à la place.
 */
final class ConfigurationPaieEnseignant
{
    public function __construct(
        public readonly string $mode,
        /** Taux horaire (mode horaire) ou montant par étudiant (gls / win-win). */
        public readonly float $taux,
        /** Ce qui manque quand `$taux` vaut 0 et que le calcul doit refuser. */
        public readonly ?string $probleme = null,
    ) {}

    public static function pour(Employee $enseignant, Carbon $mois): self
    {
        $mode = $enseignant->mode_paiement_prof;

        if ($mode === null || ! in_array($mode, Employee::MODES_PAIEMENT_PROF, true)) {
            return new self(
                mode: $mode ?? '',
                taux: 0.0,
                probleme: __('No pay mode is set on :name — fill the « Paiement prof » tab of their employee record.', [
                    'name' => $enseignant->nomComplet(),
                ]),
            );
        }

        return match ($mode) {
            Employee::MODE_PAIEMENT_HORAIRE => self::horaire($enseignant),
            Employee::MODE_PAIEMENT_GLS => self::gls($enseignant),
            Employee::MODE_PAIEMENT_WIN_WIN => self::winWin($enseignant, $mois),
        };
    }

    public function estValide(): bool
    {
        return $this->probleme === null;
    }

    private static function horaire(Employee $e): self
    {
        $taux = (float) ($e->taux_horaire_prof ?? 0);

        return $taux > 0
            ? new self(Employee::MODE_PAIEMENT_HORAIRE, $taux)
            : new self(Employee::MODE_PAIEMENT_HORAIRE, 0.0, __('No hourly rate is set on :name.', ['name' => $e->nomComplet()]));
    }

    private static function gls(Employee $e): self
    {
        $taux = (float) ($e->montant_par_etudiant_prof ?? 0);

        return $taux > 0
            ? new self(Employee::MODE_PAIEMENT_GLS, $taux)
            : new self(Employee::MODE_PAIEMENT_GLS, 0.0, __('No amount per student is set on :name.', ['name' => $e->nomComplet()]));
    }

    private static function winWin(Employee $e, Carbon $mois): self
    {
        $ligne = $e->tauxMensuels()
            ->whereDate('mois', $mois->copy()->startOfMonth()->toDateString())
            ->first();

        if ($ligne === null) {
            return new self(
                Employee::MODE_PAIEMENT_WIN_WIN,
                0.0,
                __('No win-win amount is set on :name for :month — add it in the « Paiement prof » tab.', [
                    'name' => $e->nomComplet(),
                    'month' => $mois->locale('fr')->isoFormat('MMMM YYYY'),
                ]),
            );
        }

        return new self(Employee::MODE_PAIEMENT_WIN_WIN, (float) $ligne->montant_par_etudiant);
    }
}
