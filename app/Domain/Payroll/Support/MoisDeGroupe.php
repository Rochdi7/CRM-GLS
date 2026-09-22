<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Support;

use App\Models\Group;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Les « mois » d'un groupe ne sont PAS les mois civils : ils commencent le
 * jour où le groupe a démarré.
 *
 * Un groupe parti le 07/09/2026 se paie par fenêtres 07/09 → 06/10, puis
 * 07/10 → 06/11, etc. (décision du 22/09/2026). C'est ce qui garantit
 * qu'un mois de paie couvre toujours un cycle ENTIER de cours, quel que soit
 * le jour de démarrage — un mois civil couperait le premier cycle en deux.
 *
 * Si le groupe n'a pas de `date_debut_formation`, on retombe sur le mois
 * civil : c'est le seul repère qui reste, et l'écran le signale.
 *
 * Le jour d'ancrage est PLAFONNÉ à 28 : un groupe démarré le 31 ne peut pas
 * chercher un « 31 février ». Carbon gère déjà ce cas, mais un plafond
 * explicite rend les fenêtres prévisibles (toujours la même longueur ±1).
 */
final class MoisDeGroupe
{
    public function __construct(
        public readonly Carbon $debut,
        public readonly Carbon $fin,
        /** Libellé humain : « Septembre 2026 » pour le mois civil qui contient le début. */
        public readonly string $libelle,
        /** Le 1er du mois civil du début — clé pour `enseignant_taux_mensuels`. */
        public readonly Carbon $moisCivil,
        public readonly bool $ancreSurLeGroupe,
    ) {}

    /**
     * Fenêtre du mois civil `$mois` (« 2026-09 ») pour ce groupe.
     */
    public static function pour(Group $group, string $mois): self
    {
        $civil = Carbon::createFromFormat('Y-m', $mois)?->startOfMonth()
            ?? Carbon::now()->startOfMonth();

        $ancrage = $group->date_debut_formation;

        if ($ancrage === null) {
            return new self(
                debut: $civil->copy(),
                fin: $civil->copy()->endOfMonth()->startOfDay(),
                libelle: self::libelle($civil),
                moisCivil: $civil->copy(),
                ancreSurLeGroupe: false,
            );
        }

        $jour = min((int) $ancrage->format('d'), 28);

        $debut = $civil->copy()->day($jour);
        $fin = $debut->copy()->addMonthNoOverflow()->subDay();

        return new self(
            debut: $debut,
            fin: $fin,
            libelle: self::libelle($civil),
            moisCivil: $civil->copy(),
            ancreSurLeGroupe: true,
        );
    }

    /**
     * Mois proposables pour ce groupe.
     *
     * ⚠ Les bornes sont celles des SÉANCES RÉELLES, élargies par les dates de
     * formation — jamais l'inverse. Un groupe dont `date_debut_formation` a
     * été saisie APRÈS ses premières séances (cas courant sur les données
     * importées) verrait sinon ces mois disparaître du menu : l'opérateur ne
     * pourrait pas payer un mois qui a pourtant été enseigné, sans rien à
     * l'écran pour l'expliquer.
     *
     * Symétriquement, une `date_fin_formation` dans le futur ne doit pas
     * ouvrir des mois qui n'ont pas encore eu lieu : le dernier mois
     * proposable ne dépasse jamais le mois courant.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(Group $group): array
    {
        $bornes = DB::table('seances')
            ->where('group_id', $group->id)
            ->selectRaw('min(date_seance) as premiere, max(date_seance) as derniere')
            ->first();

        $premiereSeance = $bornes?->premiere !== null ? Carbon::parse($bornes->premiere) : null;
        $derniereSeance = $bornes?->derniere !== null ? Carbon::parse($bornes->derniere) : null;

        // Le plus ANCIEN entre le début déclaré et la première séance.
        $premier = self::plusAncien($group->date_debut_formation, $premiereSeance)
            ?->copy()->startOfMonth() ?? Carbon::now()->startOfMonth();

        // Le plus RÉCENT entre la fin déclarée et la dernière séance, borné
        // au mois courant.
        $dernier = self::plusRecent($group->date_fin_formation, $derniereSeance)
            ?->copy()->startOfMonth() ?? Carbon::now()->startOfMonth();

        $moisCourant = Carbon::now()->startOfMonth();
        if ($dernier->greaterThan($moisCourant)) {
            $dernier = $moisCourant;
        }

        // Un groupe démarré dans le futur : son premier mois est proposable
        // quand même (rien à calculer, mais l'écran le dit).
        if ($dernier->lessThan($premier)) {
            $dernier = $premier->copy();
        }

        $options = [];
        for ($m = $dernier->copy(); $m->greaterThanOrEqualTo($premier); $m->subMonth()) {
            $options[] = ['value' => $m->format('Y-m'), 'label' => self::libelle($m)];
        }

        return $options;
    }

    private static function plusAncien(?Carbon $a, ?Carbon $b): ?Carbon
    {
        if ($a === null || $b === null) {
            return $a ?? $b;
        }

        return $a->lessThan($b) ? $a : $b;
    }

    private static function plusRecent(?Carbon $a, ?Carbon $b): ?Carbon
    {
        if ($a === null || $b === null) {
            return $a ?? $b;
        }

        return $a->greaterThan($b) ? $a : $b;
    }

    private static function libelle(Carbon $mois): string
    {
        return ucfirst($mois->locale('fr')->isoFormat('MMMM YYYY'));
    }
}
