<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Queries;

use App\Domain\Payroll\Actions\CalculerPaiementProfHoraire;
use App\Domain\Payroll\Actions\CalculerPaiementProfParSeance;
use App\Domain\Payroll\Support\ConfigurationPaieEnseignant;
use App\Domain\Payroll\Support\MoisDeGroupe;
use App\Domain\Payroll\Support\StatutPresencePaie;
use App\Models\Employee;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Seance;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use App\Support\Audit\HiddenAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Lit les appels réels d'un groupe sur UN MOIS DE GROUPE et en dérive le
 * paiement d'UN enseignant, selon SON mode de paie.
 *
 * Depuis le 22/09/2026 la configuration de paie vit sur l'EMPLOYÉ
 * (`ConfigurationPaieEnseignant`), et le mois n'est plus un mois civil mais
 * une fenêtre ancrée sur le jour de démarrage du groupe (`MoisDeGroupe`).
 *
 * Quatre bornes :
 *
 *  1. **L'enseignant est choisi parmi ceux affectés au groupe**, pré-rempli
 *     avec celui qui a réellement donné le plus de séances du mois. Le calcul
 *     ne porte que sur SES séances — un remplaçant est payé à part.
 *  2. **Une séance sans `enseignant_id` ne paie personne et est SIGNALÉE**
 *     (`seancesSansEnseignant`) : l'écran renvoie vers les séances pour la
 *     ré-affecter. On ne suppose jamais qui l'a donnée.
 *  3. **Seules les séances EFFECTUÉES comptent**, et elles sont à la fois le
 *     diviseur (modes gls / win-win) et le dénominateur affiché.
 *  4. **Une seule requête pour tous les étudiants** — le nombre de requêtes
 *     ne croît pas avec le nombre d'étudiants (CLAUDE.md § perf).
 */
final class GetPaiementProfCalcul
{
    public function __construct(
        private readonly CalculerPaiementProfParSeance $parSeance,
        private readonly CalculerPaiementProfHoraire $horaire,
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
    ) {}

    /** @return array<int, array{value:int,label:string}> */
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

    /**
     * Ce que le modal a besoin de savoir dès qu'un groupe est choisi : ses
     * mois, ses enseignants (avec leur mode) et celui à pré-sélectionner.
     *
     * @return array<string, mixed>
     */
    public function optionsPourGroupe(Group $group, ?string $mois = null): array
    {
        $moisOptions = MoisDeGroupe::options($group);
        $mois ??= $moisOptions[0]['value'] ?? Carbon::now()->format('Y-m');
        $fenetre = MoisDeGroupe::pour($group, $mois);

        // Tous les enseignants jamais affectés au groupe (actifs ou archivés
        // — un prof parti en cours de mois doit rester payable pour ce mois),
        // en passant par le funnel HiddenAccount comme toute liste d'employés.
        $enseignants = Employee::query()
            ->whereIn('id', GroupEnseignant::query()->where('group_id', $group->id)->select('enseignant_id'))
            ->orWhere('id', $group->enseignant_id)
            ->orderBy('nom')
            ->get();

        // Qui a RÉELLEMENT enseigné ce mois-ci ? Compte par prof, pour
        // pré-sélectionner le principal et lister les séances orphelines.
        $parProf = Seance::query()
            ->where('group_id', $group->id)
            ->where('statut', Seance::STATUT_EFFECTUEE)
            ->whereBetween('date_seance', [$fenetre->debut->toDateString(), $fenetre->fin->toDateString()])
            ->select('enseignant_id', DB::raw('count(*) as n'))
            ->groupBy('enseignant_id')
            ->pluck('n', 'enseignant_id');

        $sansEnseignant = (int) ($parProf[''] ?? $parProf[null] ?? 0);
        $parProf = $parProf->filter(fn ($n, $id) => $id !== null && $id !== '');

        $principal = $parProf->sortDesc()->keys()->first();

        return [
            'moisOptions' => $moisOptions,
            'mois' => $mois,
            'fenetre' => [
                'debut' => $fenetre->debut->toDateString(),
                'fin' => $fenetre->fin->toDateString(),
                'libelle' => $fenetre->libelle,
                'ancreSurLeGroupe' => $fenetre->ancreSurLeGroupe,
            ],
            'enseignants' => $enseignants->map(function (Employee $e) use ($parProf, $fenetre): array {
                $config = ConfigurationPaieEnseignant::pour($e, $fenetre->moisCivil);

                return [
                    'value' => $e->id,
                    'label' => $e->nomComplet(),
                    'seancesCeMois' => (int) ($parProf[$e->id] ?? 0),
                    'mode' => $config->mode,
                    'taux' => $config->taux,
                    'probleme' => $config->probleme,
                ];
            })->values()->all(),
            'enseignantParDefaut' => $principal !== null ? (int) $principal : ($group->enseignant_id ?? null),
            'seancesSansEnseignant' => $sansEnseignant,
        ];
    }

    /**
     * @param  array<int, float|null>  $ajustements  student_id => montant imposé
     * @return array<string, mixed>
     */
    public function __invoke(
        Group $group,
        Employee $enseignant,
        string $mois,
        ?float $heures = null,
        array $ajustements = [],
    ): array {
        $fenetre = MoisDeGroupe::pour($group, $mois);
        $config = ConfigurationPaieEnseignant::pour($enseignant, $fenetre->moisCivil);

        // Séances EFFECTUÉES de CET enseignant sur la fenêtre. Celles d'un
        // autre prof (ou sans prof) sont comptées à part, jamais ici.
        $seances = Seance::query()
            ->where('group_id', $group->id)
            ->where('statut', Seance::STATUT_EFFECTUEE)
            ->where('enseignant_id', $enseignant->id)
            ->whereBetween('date_seance', [$fenetre->debut->toDateString(), $fenetre->fin->toDateString()])
            ->orderBy('date_seance')
            ->get(['id', 'date_seance', 'heure_debut', 'heure_fin']);

        $sansEnseignant = Seance::query()
            ->where('group_id', $group->id)
            ->where('statut', Seance::STATUT_EFFECTUEE)
            ->whereNull('enseignant_id')
            ->whereBetween('date_seance', [$fenetre->debut->toDateString(), $fenetre->fin->toDateString()])
            ->count();

        $entete = [
            'group' => [
                'id' => $group->id,
                'nom' => $group->nom,
                'niveau' => $group->niveau,
            ],
            'enseignant' => [
                'id' => $enseignant->id,
                'nom' => $enseignant->nomComplet(),
                'mode' => $config->mode,
                'taux' => $config->taux,
                'probleme' => $config->probleme,
            ],
            'mois' => $mois,
            'periode' => [
                'debut' => $fenetre->debut->toDateString(),
                'fin' => $fenetre->fin->toDateString(),
                'libelle' => $fenetre->libelle,
                'ancreSurLeGroupe' => $fenetre->ancreSurLeGroupe,
            ],
            'seancesSansEnseignant' => $sansEnseignant,
            'heuresEffectuees' => $this->totalHeures($seances),
        ];

        // Configuration incomplète : on rend l'en-tête (l'écran a besoin du
        // problème NOMMÉ) et un calcul à zéro, jamais un chiffre deviné.
        if (! $config->estValide() || $seances->isEmpty()) {
            return [
                ...$entete,
                ...$this->parSeance->handle([], 0, $config->taux)->toArray(),
                'datesDeCours' => [],
                'grille' => [],
                'heuresSaisies' => $heures,
                'totalHoraire' => null,
            ];
        }

        // ── Mode HORAIRE : pas de lignes par étudiant ──────────────────
        if ($config->mode === Employee::MODE_PAIEMENT_HORAIRE) {
            $heuresRetenues = $heures ?? $entete['heuresEffectuees'];

            return [
                ...$entete,
                ...$this->parSeance->handle([], $seances->count(), 0.0)->toArray(),
                'datesDeCours' => $seances->pluck('date_seance')->map(fn (Carbon $d) => $d->toDateString())->all(),
                'grille' => [],
                'heuresSaisies' => $heuresRetenues,
                'totalHoraire' => $this->horaire->handle($config->taux, (float) $heuresRetenues),
                'total' => $this->horaire->handle($config->taux, (float) $heuresRetenues),
            ];
        }

        // ── Modes GLS / WIN-WIN : par étudiant ────────────────────────
        $seanceIds = $seances->pluck('id')->all();
        $datesDeCours = $seances->pluck('date_seance')
            ->map(static fn (Carbon $d): string => $d->toDateString())
            ->unique()->values()->all();

        $lignes = DB::table('presences')
            ->join('seances', 'seances.id', '=', 'presences.seance_id')
            ->join('students', 'students.id', '=', 'presences.student_id')
            ->whereIn('presences.seance_id', $seanceIds)
            ->select(['presences.student_id', 'presences.statut', 'seances.date_seance', 'students.nom', 'students.prenom'])
            ->orderBy('students.nom')->orderBy('students.prenom')
            ->get();

        $parEtudiant = [];
        $grille = [];

        foreach ($lignes as $ligne) {
            $studentId = (int) $ligne->student_id;
            $jour = Carbon::parse($ligne->date_seance)->toDateString();

            $parEtudiant[$studentId] ??= [
                'student_id' => $studentId,
                'nom' => trim(($ligne->nom ?? '').' '.($ligne->prenom ?? '')),
                'retenus' => 0, 'absents' => 0, 'ignores' => 0,
            ];

            if (StatutPresencePaie::estRetenu($ligne->statut)) {
                $parEtudiant[$studentId]['retenus']++;
            } elseif (StatutPresencePaie::estHerite($ligne->statut)) {
                $parEtudiant[$studentId]['ignores']++;
            } else {
                $parEtudiant[$studentId]['absents']++;
            }

            $grille[$studentId][$jour] = $ligne->statut;
        }

        $resultat = $this->parSeance->handle(
            etudiants: array_values($parEtudiant),
            nombreSeances: $seances->count(),
            montantParEtudiant: $config->taux,
            ajustements: $ajustements,
        );

        return [
            ...$entete,
            ...$resultat->toArray(),
            'datesDeCours' => $datesDeCours,
            'grille' => $grille,
            'heuresSaisies' => null,
            'totalHoraire' => null,
        ];
    }

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

    private function scopeToActiveCenter(Builder $query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
