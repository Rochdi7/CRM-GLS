<?php

declare(strict_types=1);

namespace App\Domain\Payroll\Queries;

use App\Models\Depense;
use App\Models\Employee;
use App\Models\Group;
use App\Models\GroupEnseignant;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\TypeDepense;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * L'espace d'UN enseignant : ses groupes, ses séances du mois, et l'historique
 * de ce qu'il a touché (23/09/2026).
 *
 * ⚠ Ce read-model ne lit que ce qui appartient à L'ENSEIGNANT DONNÉ. Il est
 * servi au prof lui-même sur son tableau de bord (`teacher` n'a AUCUN accès
 * finance : c'est la seule fenêtre qu'il a sur l'argent, et elle ne montre
 * que le sien), et à la direction depuis la fiche employé. L'identité vient
 * TOUJOURS du serveur — `$user->employee`, jamais un id du client — sinon un
 * prof lirait la paie d'un autre en changeant un chiffre dans l'URL.
 *
 * Trois règles de lecture :
 *
 *  1. **Un paiement porte SON enseignant** (`depenses.enseignant_id`, figé à
 *     la création). Les lignes antérieures à la colonne (NULL) sont
 *     rattachées au prof ACTUEL du groupe, et la ligne le DIT
 *     (`enseignantDeduit`) : ce n'est pas la même certitude, l'écran ne doit
 *     pas la faire passer pour telle. Jamais de backfill (§11).
 *  2. **Le statut est affiché, jamais masqué** : « En attente » n'est pas de
 *     l'argent sorti, « Refusée » / « Annulée » n'en est plus. Le cumul ne
 *     somme que « Approuvée » — la MÊME définition d'« argent sorti » que
 *     toutes les lectures de caisse (CLAUDE.md §11).
 *  3. **Une requête par bloc, jamais une par ligne** (§ perf) : les séances
 *     du mois sont agrégées par groupe en SQL, les présences en un seul
 *     GROUP BY.
 */
final class GetEspaceEnseignant
{
    public function __construct(private readonly CurrentContext $context) {}

    /** @return array<string, mixed> */
    public function __invoke(Employee $enseignant, ?string $mois = null): array
    {
        $moisCivil = $mois !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mois) === 1
            ? Carbon::createFromFormat('Y-m', $mois)->startOfMonth()
            : Carbon::now()->startOfMonth();

        return [
            'enseignant' => [
                'id' => $enseignant->id,
                'nom' => $enseignant->nomComplet(),
                'mode' => $enseignant->mode_paiement_prof,
            ],
            'mois' => $moisCivil->format('Y-m'),
            'moisLibelle' => ucfirst($moisCivil->locale('fr')->isoFormat('MMMM YYYY')),
            'groupes' => $this->groupesDuMois($enseignant, $moisCivil),
            'paiements' => $this->paiements($enseignant),
            'cumul' => $this->cumulAnnee($enseignant),
        ];
    }

    /**
     * Ses groupes, avec ce qu'il y a enseigné CE MOIS : séances effectuées et
     * taux de présence de ses étudiants.
     *
     * @return list<array<string, mixed>>
     */
    private function groupesDuMois(Employee $enseignant, Carbon $mois): array
    {
        $debut = $mois->toDateString();
        $fin = $mois->copy()->endOfMonth()->toDateString();

        // Les groupes où il est ou a été affecté, dans l'année active.
        $groupIds = GroupEnseignant::query()
            ->where('enseignant_id', $enseignant->id)
            ->select('group_id');

        $groupes = Group::query()
            ->where(fn ($q) => $q->whereIn('id', $groupIds)->orWhere('enseignant_id', $enseignant->id))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->orderBy('nom')
            ->get(['id', 'nom', 'niveau', 'statut', 'enseignant_id']);

        if ($groupes->isEmpty()) {
            return [];
        }

        $ids = $groupes->pluck('id')->all();

        // UNE requête : séances effectuées PAR CE PROF, par groupe, ce mois.
        $seancesParGroupe = Seance::query()
            ->whereIn('group_id', $ids)
            ->where('enseignant_id', $enseignant->id)
            ->where('statut', Seance::STATUT_EFFECTUEE)
            ->whereBetween('date_seance', [$debut, $fin])
            ->select('group_id', DB::raw('count(*) as n'))
            ->groupBy('group_id')
            ->pluck('n', 'group_id');

        // UNE requête : présents / appelés, par groupe, sur ces mêmes séances.
        $presencesParGroupe = DB::table('presences')
            ->join('seances', 'seances.id', '=', 'presences.seance_id')
            ->whereIn('seances.group_id', $ids)
            ->where('seances.enseignant_id', $enseignant->id)
            ->where('seances.statut', Seance::STATUT_EFFECTUEE)
            ->whereBetween('seances.date_seance', [$debut, $fin])
            ->select(
                'seances.group_id',
                DB::raw('count(*) as appels'),
                DB::raw("sum(case when presences.statut = '".Presence::STATUT_PRESENT."' then 1 else 0 end) as presents"),
            )
            ->groupBy('seances.group_id')
            ->get()
            ->keyBy('group_id');

        return $groupes->map(function (Group $g) use ($seancesParGroupe, $presencesParGroupe, $enseignant): array {
            $p = $presencesParGroupe->get($g->id);
            $appels = (int) ($p->appels ?? 0);
            $presents = (int) ($p->presents ?? 0);

            return [
                'id' => $g->id,
                'nom' => $g->nom,
                'niveau' => $g->niveau,
                'statut' => $g->statut,
                // Est-il ENCORE le prof du groupe ? Un groupe qu'il a quitté
                // reste listé (il y a enseigné ce mois) mais se lit autrement.
                'actif' => (int) $g->enseignant_id === (int) $enseignant->id,
                'seancesCeMois' => (int) ($seancesParGroupe[$g->id] ?? 0),
                'appels' => $appels,
                'presents' => $presents,
                'tauxPresence' => $appels > 0 ? round($presents / $appels * 100) : null,
            ];
        })->values()->all();
    }

    /**
     * Ses « Paiement prof », les plus récents d'abord, TOUS statuts.
     *
     * @return list<array<string, mixed>>
     */
    private function paiements(Employee $enseignant): array
    {
        $typeId = TypeDepense::query()->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)->value('id');

        if ($typeId === null) {
            return [];
        }

        // Explicite : la ligne porte son id. Repli : ligne ANTÉRIEURE à la
        // colonne (NULL) dont le groupe l'a AUJOURD'HUI pour prof. Les deux
        // cas sont distingués ligne par ligne (`enseignantDeduit`).
        $rows = Depense::query()
            ->where('type_depense_id', $typeId)
            ->where(function ($q) use ($enseignant) {
                $q->where('enseignant_id', $enseignant->id)
                    ->orWhere(function ($q2) use ($enseignant) {
                        $q2->whereNull('enseignant_id')
                            ->whereIn('group_id', Group::query()->where('enseignant_id', $enseignant->id)->select('id'));
                    });
            })
            ->with(['group:id,nom'])
            ->orderByDesc('date_depense')
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        return $rows->map(fn (Depense $d): array => [
            'id' => $d->id,
            'reference' => $d->reference,
            'date' => $d->date_depense?->toDateString(),
            'periodeDebut' => $d->periode_debut?->toDateString(),
            'periodeFin' => $d->periode_fin?->toDateString(),
            'groupNom' => $d->group?->nom,
            'montant' => (float) $d->montant,
            'statut' => $d->statut,
            'description' => $d->description,
            // Vrai quand le prof n'est pas ÉCRIT sur la ligne : rattaché par
            // le groupe, à afficher comme tel.
            'enseignantDeduit' => $d->enseignant_id === null,
        ])->all();
    }

    /**
     * Cumul de l'année scolaire ACTIVE : uniquement « Approuvée » (argent
     * sorti), plus le montant encore « En attente » à part.
     *
     * @return array{approuve: float, enAttente: float, nombre: int}
     */
    private function cumulAnnee(Employee $enseignant): array
    {
        $typeId = TypeDepense::query()->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)->value('id');
        $range = $this->context->anneeDateRange();

        if ($typeId === null) {
            return ['approuve' => 0.0, 'enAttente' => 0.0, 'nombre' => 0];
        }

        $base = Depense::query()
            ->where('type_depense_id', $typeId)
            ->where(function ($q) use ($enseignant) {
                $q->where('enseignant_id', $enseignant->id)
                    ->orWhere(function ($q2) use ($enseignant) {
                        $q2->whereNull('enseignant_id')
                            ->whereIn('group_id', Group::query()->where('enseignant_id', $enseignant->id)->select('id'));
                    });
            })
            ->when($range !== null, fn ($q) => $q->whereBetween('date_depense', $range));

        $agg = (clone $base)
            ->selectRaw("
                coalesce(sum(case when statut = ? then montant else 0 end), 0) as approuve,
                coalesce(sum(case when statut = ? then montant else 0 end), 0) as en_attente,
                count(*) as nombre
            ", [Depense::STATUT_APPROUVEE, Depense::STATUT_EN_ATTENTE])
            ->first();

        return [
            'approuve' => (float) ($agg->approuve ?? 0),
            'enAttente' => (float) ($agg->en_attente ?? 0),
            'nombre' => (int) ($agg->nombre ?? 0),
        ];
    }
}
