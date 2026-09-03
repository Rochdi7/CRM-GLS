<?php

declare(strict_types=1);

namespace App\Domain\Groups\Queries;

use App\Domain\Attendance\Support\DiagnostiquerEmploiDuTemps;
use App\Models\Frais;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-model for the Groups list — extracted verbatim from
 * GroupsIndex::render() (same eager loads/counts, same center/year scoping,
 * same status filter, same search column, same `latest()` ordering, same
 * per-status tab counts) so the React page and the legacy Livewire page stay
 * byte-for-byte behaviorally identical while both exist.
 */
final class GetGroupsList
{
    /** @var list<int> */
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
        private readonly DiagnostiquerEmploiDuTemps $diagnostic,
    ) {}

    public function __invoke(
        User $user,
        string $search = '',
        string $statutFilter = Group::STATUT_EN_FORMATION,
        int $perPage = self::DEFAULT_PER_PAGE,
        string $enseignantFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
    ): LengthAwarePaginator {
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::DEFAULT_PER_PAGE;
        }

        $groups = Group::query()
            ->with(['enseignant', 'frais'])
            ->withCount([
                'inscriptions',
                'inscriptions as inscriptions_actives_count' => fn ($q) => $q->where('statut', Inscription::STATUT_ACTIVE),
                'inscriptions as inscriptions_annulees_count' => fn ($q) => $q->where('statut', Inscription::STATUT_ANNULEE),
                'inscriptions as inscriptions_changement_count' => fn ($q) => $q->where('statut', Inscription::STATUT_CHANGEMENT),
                'inscriptions as etudiants_distincts_count' => fn ($q) => $q->select(DB::raw('COUNT(DISTINCT student_id)')),
                // Emploi du temps : total des créneaux vs ceux encore OUVERTS.
                // Des créneaux existants mais aucun ouvert = emploi du temps
                // arrêté par un changement d'enseignant et jamais refait, donc
                // plus aucune séance générée (voir GetGroupDetails). Deux
                // withCount agrégés — jamais une requête par ligne (§ perf).
                'creneaux',
                'creneaux as creneaux_ouverts_count' => fn ($q) => $q->whereNull('date_fin'),
            ])
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            // The "Historique" tab groups both terminal statuses together
            // (Fin de formation + Annulée) — its tab key stays the single
            // value 'Fin de formation' (filters.statutFilter, tab active-
            // state matching), but the actual query matches the whole set.
            ->when(
                $statutFilter === Group::STATUT_FIN_FORMATION,
                fn ($q) => $q->whereIn('statut', Group::STATUTS_HISTORIQUE),
                fn ($q) => $q->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter)),
            )
            ->when($enseignantFilter !== '', fn ($q) => $q->where('enseignant_id', (int) $enseignantFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_debut_formation', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('date_debut_formation', '<=', $dateTo))
            ->when($search !== '', fn ($q) => $q->where('nom', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        // Active catalog, fetched ONCE for the whole page (never per row):
        // used to derive each group's « Frais retirés » — the catalog fees
        // it no longer carries, which the edit modal offers for restore.
        $catalogueActif = Frais::query()
            ->where('statut', Frais::STATUT_ACTIF)
            ->orderBy('nom')
            ->get(['id', 'nom']);

        $groups->through(fn (Group $group): array => [
            'id' => $group->id,
            'nom' => $group->nom,
            'niveau' => $group->niveau,
            'enseignant' => $group->enseignant?->nomComplet(),
            'enseignantId' => $group->enseignant_id,
            'dateDebutFormation' => $group->date_debut_formation?->toDateString(),
            'dateFinFormation' => $group->date_fin_formation?->toDateString(),
            'statut' => $group->statut,
            'inscriptionsCount' => $group->inscriptions_count,
            'inscriptionsActivesCount' => $group->inscriptions_actives_count,
            'inscriptionsAnnuleesCount' => $group->inscriptions_annulees_count,
            'inscriptionsChangementCount' => $group->inscriptions_changement_count,
            'etudiantsDistinctsCount' => $group->etudiants_distincts_count,
            'fraisCount' => $group->frais_count,
            // Voir le commentaire du withCount ci-dessus : la cause exacte est
            // signalée dès la liste, pour que le problème se voie sans ouvrir
            // chaque fiche une par une.
            'emploiDuTempsProbleme' => ($this->diagnostic)(
                $group,
                (int) $group->creneaux_count,
                (int) $group->creneaux_ouverts_count,
            ),
            'showUrl' => route('backoffice.groups.show', $group),
            // Keyed by frais_id so the edit modal can prefill the fee-lines
            // table without a second request — same data Group::with('frais')
            // already gives the Livewire component's edit().
            'fraisLignes' => $group->frais->mapWithKeys(fn ($fee): array => [
                $fee->id => [
                    'montant' => (string) $fee->pivot->montant,
                    'dateEcheance' => $fee->pivot->date_echeance ?: '',
                    'classification' => $fee->pivot->classification ?: '',
                ],
            ])->all(),
            // Catalog fees this group NO LONGER carries — the edit modal's
            // « Frais retirés » list, the only place a removed fee can be
            // restored from (mirrors the Inscriptions modal's « Frais
            // masqués »). Derived, not stored: a fee is "removed" for this
            // group precisely when the active catalog offers it and
            // group_frais has no row for it.
            'fraisRetires' => $catalogueActif
                ->whereNotIn('id', $group->frais->pluck('id'))
                ->map(fn (Frais $frais): array => ['id' => $frais->id, 'nom' => $frais->nom])
                ->values()
                ->all(),
        ]);

        return $groups;
    }

    /**
     * Per-status counts for the tab badges (same center/year scope, ignores
     * search). The "Historique" tab's badge (keyed by 'Fin de formation',
     * matching the tab's own filter value) sums both terminal statuses —
     * see the same combined-set rationale in __invoke() above.
     *
     * @return Collection<string, int>
     */
    public function statutCounts(User $user): Collection
    {
        $perStatut = Group::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $historiqueTotal = collect(Group::STATUTS_HISTORIQUE)->sum(fn (string $statut) => $perStatut->get($statut, 0));

        return $perStatut->put(Group::STATUT_FIN_FORMATION, $historiqueTotal);
    }

    private function scopeToActiveCenter($query): void
    {
        $id = $this->context->etablissementId();

        if ($id === null) {
            return;
        }

        $query->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $id));
    }
}
