<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Queries;

use App\Domain\Payroll\Actions\CalculerPaiementProfHebdomadaire;
use App\Domain\Payroll\Actions\CalculerPaiementProfHoraire;
use App\Domain\Payroll\Support\StatutPresencePaie;
use App\Models\Group;
use App\Models\Seance;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lit les appels réels d'un groupe sur une période et en dérive le paiement
 * de l'enseignant.
 *
 * C'est ici que se trouve la différence de fond avec le portail GLS : là-bas
 * les présences arrivaient par un import Excel / une API, et il fallait les
 * stocker dans des tables d'instantané (`presence_imports`,
 * `presence_import_students`, `presence_records`). Ici la donnée nous
 * appartient — `presences` → `seances` —, donc AUCUNE table d'import n'est
 * nécessaire : on calcule à la lecture, à partir de la source de vérité.
 *
 * Trois bornes :
 *
 *  1. **Une seule requête pour tous les étudiants** — jamais une par ligne.
 *     L'agrégation (jours retenus par étudiant × semaine ISO) est faite par
 *     PostgreSQL ; le nombre de requêtes ne croît pas avec le nombre
 *     d'étudiants (CLAUDE.md § perf).
 *  2. **Seules les séances EFFECTUÉES comptent.** Une séance « Prévue » n'a
 *     pas eu lieu et une séance « Annulée » n'a rien enseigné : les payer
 *     reviendrait à rémunérer un cours qui n'existe pas.
 *  3. **Retard et Justifié sont ignorés** (`StatutPresencePaie`) — ni pour,
 *     ni contre. La règle a une seule définition, partagée avec la grille.
 */
final class GetPaiementProfCalcul
{
    public function __construct(
        private readonly CalculerPaiementProfHebdomadaire $hebdomadaire,
        private readonly CalculerPaiementProfHoraire $horaire,
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /**
     * Groupes proposés au choix — même entonnoir que toute autre liste
     * (CLAUDE.md §11) : portée « Centres affectés », puis centre ET année
     * actifs de la barre du haut.
     *
     * @return array<int, array{value:int,label:string}>
     */
    public function groupOptions(User $user): array
    {
        return Group::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn (Group $g): array => ['value' => $g->id, 'label' => (string) $g->nom])
            ->all();
    }

    private function scopeToActiveCenter(Builder $query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }

    /**
     * @param  array<int, float|null>  $ajustements  student_id => montant imposé
     * @return array<string, mixed>
     */
    public function __invoke(
        Group $group,
        string $dateDebut,
        string $dateFin,
        ?float $montantParEtudiant = null,
        ?int $seuil = null,
        array $ajustements = [],
    ): array {
        $seuil ??= CalculerPaiementProfHebdomadaire::SEUIL_PAR_DEFAUT;
        $montantParEtudiant ??= (float) ($group->montant_par_etudiant_prof ?? 0);

        $debut = Carbon::parse($dateDebut)->startOfDay();
        $fin = Carbon::parse($dateFin)->startOfDay();

        // Séances RÉELLEMENT effectuées du groupe sur la période.
        $seances = Seance::query()
            ->where('group_id', $group->id)
            ->where('statut', Seance::STATUT_EFFECTUEE)
            ->whereBetween('date_seance', [$debut->toDateString(), $fin->toDateString()])
            ->orderBy('date_seance')
            ->get(['id', 'date_seance', 'heure_debut', 'heure_fin']);

        if ($seances->isEmpty()) {
            return $this->resultatVide($group, $montantParEtudiant, $seuil, $debut, $fin);
        }

        $seanceIds = $seances->pluck('id')->all();
        $datesDeCours = $seances->pluck('date_seance')
            ->map(static fn (Carbon $d): string => $d->toDateString())
            ->unique()
            ->values()
            ->all();

        // UNE requête : par étudiant et par statut, le détail des jours.
        // On récupère les lignes d'appel avec leur date pour pouvoir à la fois
        // agréger par semaine ISO (le calcul) et peindre la grille (l'écran)
        // sans repasser en base.
        $lignes = DB::table('presences')
            ->join('seances', 'seances.id', '=', 'presences.seance_id')
            ->join('students', 'students.id', '=', 'presences.student_id')
            ->whereIn('presences.seance_id', $seanceIds)
            ->select([
                'presences.student_id',
                'presences.statut',
                'seances.date_seance',
                'students.nom',
                'students.prenom',
            ])
            ->orderBy('students.nom')
            ->orderBy('students.prenom')
            ->get();

        if ($lignes->isEmpty()) {
            return $this->resultatVide($group, $montantParEtudiant, $seuil, $debut, $fin);
        }

        // Regroupement en mémoire sur un jeu déjà borné (un groupe × une
        // période) : c'est le SEUL passage PHP, et il ne refait aucune requête.
        $parEtudiant = [];
        $grille = [];

        foreach ($lignes as $ligne) {
            $studentId = (int) $ligne->student_id;
            $jour = Carbon::parse($ligne->date_seance)->toDateString();
            $semaineIso = Carbon::parse($ligne->date_seance)->isoFormat('GGGG-WW');

            $parEtudiant[$studentId] ??= [
                'student_id' => $studentId,
                'nom' => trim(($ligne->nom ?? '').' '.($ligne->prenom ?? '')),
                'semaines' => [],
                'retenus' => 0,
                'absents' => 0,
                'ignores' => 0,
            ];

            if (StatutPresencePaie::estRetenu($ligne->statut)) {
                $parEtudiant[$studentId]['semaines'][$semaineIso] =
                    ($parEtudiant[$studentId]['semaines'][$semaineIso] ?? 0) + 1;
                $parEtudiant[$studentId]['retenus']++;
            } elseif (StatutPresencePaie::estIgnore($ligne->statut)) {
                // Ni pour, ni contre — la ligne sort du calcul.
                $parEtudiant[$studentId]['ignores']++;
            } else {
                $parEtudiant[$studentId]['absents']++;
            }

            // Grille d'affichage : statut brut par (étudiant, jour).
            $grille[$studentId][$jour] = $ligne->statut;
        }

        $resultat = $this->hebdomadaire->handle(
            etudiants: array_values($parEtudiant),
            datesDeCours: $datesDeCours,
            montantParEtudiant: $montantParEtudiant,
            seuil: $seuil,
            ajustements: $ajustements,
        );

        // Heures réellement enseignées, dérivées des séances effectuées —
        // sert au mode horaire, qui n'a pas de lignes par étudiant.
        $heures = $this->totalHeures($seances);

        return [
            ...$resultat->toArray(),
            'group' => [
                'id' => $group->id,
                'nom' => $group->nom,
                'niveau' => $group->niveau,
                'enseignantNom' => $group->enseignant?->nomComplet(),
                'montantParEtudiantDefaut' => $group->montant_par_etudiant_prof !== null
                    ? (float) $group->montant_par_etudiant_prof
                    : null,
                'tauxHoraireDefaut' => $group->taux_horaire_prof !== null
                    ? (float) $group->taux_horaire_prof
                    : null,
            ],
            'periode' => [
                'debut' => $debut->toDateString(),
                'fin' => $fin->toDateString(),
            ],
            'datesDeCours' => $datesDeCours,
            'grille' => $grille,
            'heuresEffectuees' => $heures,
            'totalHoraire' => $group->taux_horaire_prof !== null
                ? $this->horaire->handle((float) $group->taux_horaire_prof, $heures)
                : null,
            'nombreSeances' => $seances->count(),
        ];
    }

    /**
     * Total des heures des séances effectuées. Une séance sans horaire
     * renseigné compte 0 — mieux vaut un total visiblement bas qu'une durée
     * inventée par défaut.
     */
    private function totalHeures(iterable $seances): float
    {
        $minutes = 0;

        foreach ($seances as $seance) {
            if ($seance->heure_debut === null || $seance->heure_fin === null) {
                continue;
            }

            $debut = Carbon::parse($seance->heure_debut);
            $fin = Carbon::parse($seance->heure_fin);

            if ($fin->lessThanOrEqualTo($debut)) {
                continue;
            }

            $minutes += $debut->diffInMinutes($fin);
        }

        return round($minutes / 60, 2);
    }

    /** @return array<string, mixed> */
    private function resultatVide(
        Group $group,
        float $montantParEtudiant,
        int $seuil,
        Carbon $debut,
        Carbon $fin,
    ): array {
        $vide = $this->hebdomadaire->handle([], [], $montantParEtudiant, $seuil);

        return [
            ...$vide->toArray(),
            'group' => [
                'id' => $group->id,
                'nom' => $group->nom,
                'niveau' => $group->niveau,
                'enseignantNom' => $group->enseignant?->nomComplet(),
                'montantParEtudiantDefaut' => $group->montant_par_etudiant_prof !== null
                    ? (float) $group->montant_par_etudiant_prof
                    : null,
                'tauxHoraireDefaut' => $group->taux_horaire_prof !== null
                    ? (float) $group->taux_horaire_prof
                    : null,
            ],
            'periode' => ['debut' => $debut->toDateString(), 'fin' => $fin->toDateString()],
            'datesDeCours' => [],
            'grille' => [],
            'heuresEffectuees' => 0.0,
            'totalHoraire' => null,
            'nombreSeances' => 0,
        ];
    }
}
