<?php

declare(strict_types=1);

namespace App\Domain\Expenses\Queries;

use App\Domain\Finance\Support\VentilationCentre;
use App\Models\Depense;
use App\Models\Group;
use App\Models\TypeDepense;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-model for the Depenses list — extracted verbatim from
 * DepensesIndex::render() (center-scoping through the caisse relation, same
 * type/caisse/date-range/search filters, `montantTotal` computed over the
 * FULL filtered set, not just the current page).
 *
 * The "Paiement prof" system type is split out of the main list into its own
 * tab (see $scope) — those rows stay ordinary dépenses in every other
 * respect (same table, same caisse, same money invariants), they are only
 * listed apart so the Dépenses table stays readable.
 */
final class GetDepensesList
{
    public const DEFAULT_PER_PAGE = 10;

    /** Everything EXCEPT the "Paiement prof" system type — the Dépenses tab. */
    public const SCOPE_HORS_PAIEMENT_PROF = 'hors-paiement-prof';

    /** ONLY the "Paiement prof" system type — the Paiements prof tab. */
    public const SCOPE_PAIEMENT_PROF = 'paiement-prof';

    /**
     * EVERY dépense, both kinds — the « Validation des dépenses » tab.
     *
     * The Dépenses / Paiements prof split above is a READABILITY choice for
     * the two browsing tabs. Validation is not browsing: it is the single
     * screen where a pending dépense is approved or refused, and a row it
     * does not list can never be decided — its money stays held in the till
     * forever. Reported 04/09/2026: 10 « Paiement prof » rows (30 025.50 MAD
     * across 4 tills, one of them heading negative on a duplicate) were
     * invisible because this tab reused the HORS_PAIEMENT_PROF list.
     */
    public const SCOPE_TOUS = 'tous';

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
        private readonly VentilationCentre $ventilation,
    ) {}

    /**
     * @return array{data: LengthAwarePaginator, montantTotal: string, montantEnAttente: string, enAttenteCount: int}
     */
    public function __invoke(
        User $user,
        string $search = '',
        string $typeFilter = '',
        string $caisseFilter = '',
        string $dateFrom = '',
        string $dateTo = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        string $scope = self::SCOPE_HORS_PAIEMENT_PROF,
        string $statutFilter = '',
        bool $dateFilterEngaged = false,
    ): array {
        $paiementProfId = $this->paiementProfTypeId();

        $base = Depense::query()
            // ⚠ A dépense is scoped by ITS OWN centre (VentilationCentre —
            // group, else the centre it was keyed in, else the till's for
            // rows older than the column), never by `caisses.etablissement_id`
            // alone (18/09/2026): an employee's single till is attached to
            // their PRIMARY centre, so an expense keyed while working in GLS
            // Online was listed under the primary centre and missing from
            // Online — the screen it had just been created on.
            ->tap(fn (Builder $q) => $this->scopeToReachableCenters($q, $user))
            ->when(
                $this->context->etablissementId() !== null,
                fn (Builder $q) => $this->ventilation->scopeDepensesAuCentre($q, (int) $this->context->etablissementId(), true),
            )
            ->when($typeFilter !== '', fn ($q) => $q->where('type_depense_id', (int) $typeFilter))
            // Tab split. When the type row is missing (never seeded), the
            // Paiements prof tab is simply empty and nothing is hidden from
            // the Dépenses tab.
            ->when(
                $paiementProfId !== null && $scope !== self::SCOPE_TOUS,
                fn ($q) => $scope === self::SCOPE_PAIEMENT_PROF
                    ? $q->where('type_depense_id', $paiementProfId)
                    : $q->where(fn ($sub) => $sub->whereNot('type_depense_id', $paiementProfId)->orWhereNull('type_depense_id')),
                fn ($q) => $scope === self::SCOPE_PAIEMENT_PROF ? $q->whereRaw('1 = 0') : $q,
            )
            ->when($caisseFilter !== '', fn ($q) => $q->where('caisse_id', (int) $caisseFilter))
            ->when($statutFilter !== '', fn ($q) => $q->where('statut', $statutFilter))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_depense', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('date_depense', '<=', $dateTo))
            // Year switcher: a dépense belongs to the year its date falls
            // in. The active year is only the DEFAULT window — an explicit
            // date filter states the user's intent and takes over.
            //
            // ⚠ `$anneeWindowApplies` is decided ONCE, from whether the user
            // has engaged the date filter AT ALL — not re-evaluated per edge
            // (audit 07/09/2026, H-1). Keying it on `$dateFrom === '' &&
            // $dateTo === ''` re-armed the whole year window the moment the
            // LAST date was cleared, so a row from another year that the
            // user had deliberately surfaced with « Du » vanished again when
            // they cleared it. §5: clearing a filter must only ever WIDEN a
            // result set. `$dateFilterEngaged` is passed by the controller,
            // which alone can tell « never touched » from « cleared ».
            ->when(
                ! $dateFilterEngaged && $this->context->anneeDateRange() !== null,
                fn ($q) => $q->whereBetween('date_depense', $this->context->anneeDateRange()),
            )
            ->when($search !== '', function ($q) use ($search): void {
                $term = "%{$search}%";
                $q->where(function ($sub) use ($term): void {
                    $sub->where('reference', 'ilike', $term)
                        ->orWhere('description', 'ilike', $term)
                        ->orWhere('mots_cles', 'ilike', $term);
                });
            });

        // Two separate totals, because they mean different things:
        // - montantTotal      = money that actually LEFT the tills (approved
        //   only). A pending or refused expense has debited nothing, so
        //   counting it here would overstate the spend.
        // - montantEnAttente  = money awaiting a decision (on hold).
        $montantTotal = (clone $base)->where('statut', Depense::STATUT_APPROUVEE)->sum('montant');
        $enAttenteBase = (clone $base)->where('statut', Depense::STATUT_EN_ATTENTE);
        $montantEnAttente = (clone $enAttenteBase)->sum('montant');
        $enAttenteCount = (clone $enAttenteBase)->count();

        $depenses = $base
            // `group` is eager-loaded for the Validation tab's Groupe column
            // — without it every row would fire its own query.
            // Les trois chaînes de `centreNom()` sont chargées ici : sans
            // elles la colonne Centre déclencherait une requête PAR LIGNE.
            ->with([
                'typeDepense', 'agent', 'approvedBy',
                'caisse.etablissement', 'group.etablissement', 'etablissement',
            ])
            ->withCount('media')
            ->latest()
            // Each tab paginates independently, so the Paiements prof tab
            // uses its own page query-string key.
            ->paginate($perPage, ['*'], match ($scope) {
                self::SCOPE_PAIEMENT_PROF => 'pageProf',
                self::SCOPE_TOUS => 'pageValidation',
                default => 'page',
            })
            ->withQueryString();

        $depenses->through(fn (Depense $d): array => [
            'id' => $d->id,
            'reference' => $d->reference,
            'typeDepense' => $d->typeDepense?->nom,
            'typeDepenseId' => $d->type_depense_id,
            'caisse' => $d->caisse?->nom,
            'caisseId' => $d->caisse_id,
            // Le centre auquel la dépense est IMPUTÉE — `Depense::centreId()`,
            // la même règle que le filtrage ci-dessus (groupe, sinon centre de
            // saisie, sinon caisse pour les lignes antérieures à la colonne).
            // Jamais `caisse.etablissement_id` seul : une employée n'a qu'UNE
            // caisse, rattachée à son centre principal, donc la colonne
            // afficherait ce centre-là pour une dépense saisie ailleurs — et
            // contredirait le filtre qui, lui, l'a bien classée (§11 : un
            // écran ne montre jamais une valeur que son propre filtre ignore).
            'etablissement' => self::centreNom($d),
            'groupId' => $d->group_id,
            'groupNom' => $d->group?->nom,
            'montant' => number_format((float) $d->montant, 2, '.', ''),
            'methodePaiement' => $d->methode_paiement,
            'dateDepense' => $d->date_depense?->toDateString(),
            // « Paiement prof » only — the teaching period the payment
            // covers (null on every ordinary dépense).
            'periodeDebut' => $d->periode_debut?->toDateString(),
            'periodeFin' => $d->periode_fin?->toDateString(),
            'referenceFacture' => $d->reference_facture,
            'description' => $d->description,
            'motsCles' => $d->mots_cles,
            'note' => $d->note,
            'agent' => $d->agent?->nomComplet(),
            'receiptsCount' => $d->media_count,
            'statut' => $d->statut,
            'isEnAttente' => $d->isEnAttente(),
            'isRefusee' => $d->isRefusee(),
            'isAnnulee' => $d->isAnnulee(),
            // La raison, extraite de la note que AnnulerDepense y a écrite —
            // affichée sous le badge comme le motif de refus, pour que la
            // ligne s'explique sans ouvrir la fiche.
            'motifAnnulation' => $d->isAnnulee() ? self::motifAnnulation($d) : null,
            'approvedBy' => $d->approvedBy?->nomComplet(),
            'approvedAt' => $d->approved_at?->toDateTimeString(),
            'motifRefus' => $d->motif_refus,
            // Operation trail — WHEN the row was actually keyed in and last
            // touched, as opposed to `dateDepense` which is the business date
            // the user types and can backdate freely. Shown to super-admins
            // only (DepenseController passes `canViewOperationDates`); the
            // two differing is exactly what an auditor looks for.
            'createdAt' => $d->created_at?->format('d/m/Y H:i'),
            'updatedAt' => $d->updated_at?->format('d/m/Y H:i'),
            // Cheap client-side flag so the column can show "modifiée le …"
            // only when it really was edited after creation. Second-level
            // compare: created_at/updated_at are written microseconds apart
            // on insert, so === on the formatted minute would be fragile.
            // ⚠ abs(): Carbon 3's diffInSeconds() is SIGNED, so the raw
            // value flips with argument order and a plain `> 1` silently
            // never fires. The 1s floor ignores the microseconds between
            // created_at and updated_at on insert.
            'wasEdited' => $d->created_at !== null
                && $d->updated_at !== null
                && abs($d->updated_at->diffInSeconds($d->created_at)) > 1,
            'showUrl' => route('backoffice.depenses.show', $d),
        ]);

        return [
            'data' => $depenses,
            'montantTotal' => number_format((float) $montantTotal, 2, '.', ''),
            'montantEnAttente' => number_format((float) $montantEnAttente, 2, '.', ''),
            'enAttenteCount' => $enAttenteCount,
        ];
    }

    /**
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function groupOptions(User $user): Collection
    {
        return Group::query()
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            // Same year scoping as every other group dropdown (e.g.
            // GetRetardsList::groupOptions) — without it the "Paiement prof"
            // form offers groups from every past year.
            ->when($this->context->anneeScolaireId(), fn ($q, $y) => $q->where('annee_scolaire_id', $y))
            ->orderBy('nom')
            ->get()
            ->map(fn (Group $g): array => ['id' => $g->id, 'nom' => $g->nom]);
    }

    /**
     * Les groupes des années PRÉCÉDENTES, pour la case « Afficher les
     * groupes des années précédentes » du modal « Paiement prof »
     * (11/09/2026). Un enseignant est réglé APRÈS coup : la prestation
     * d'un groupe terminé en juin se paie en septembre, quand le sélecteur
     * du haut est déjà passé à la nouvelle année. Sans cette liste le
     * paiement se saisissait sans groupe — donc hors de tout récapitulatif
     * par groupe — ou pire, sur un homonyme de l'année en cours.
     *
     * Deux bornes que cette liste ne relâche JAMAIS :
     *  - le CENTRE reste celui du contexte actif et de la portée de
     *    l'utilisateur (`scopeAccessibleCenters` + `scopeToActiveCenter`) —
     *    c'est l'année seule qui s'ouvre, jamais le centre ;
     *  - une année CLÔTURÉE est exclue : aucune écriture n'y est acceptée
     *    (`AssertsContextScope::assertAnneeNotCloturee`), donc l'offrir ici
     *    ferait proposer un choix que le serveur refuse (§5 : un
     *    read-model ne promet jamais ce que l'action interdit).
     *
     * Le libellé PORTE l'année (« Ilyass 19H — 2024/2025 ») : deux groupes
     * homonymes d'années différentes sont indiscernables sans elle, et
     * c'est précisément l'erreur que la case ouvre la porte à commettre.
     *
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function groupOptionsAnneesPrecedentes(User $user): Collection
    {
        $anneeActive = $this->context->anneeScolaireId();

        if ($anneeActive === null) {
            return collect();
        }

        return Group::query()
            ->with('anneeScolaire')
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->where('annee_scolaire_id', '!=', $anneeActive)
            ->whereHas('anneeScolaire', fn ($q) => $q->ouvertes())
            ->orderByDesc('annee_scolaire_id')
            ->orderBy('nom')
            ->get()
            ->map(fn (Group $g): array => [
                'id' => $g->id,
                'nom' => $g->anneeScolaire !== null
                    ? $g->nom.' — '.$g->anneeScolaire->nom
                    : $g->nom,
            ]);
    }

    /** id of the seeded "Paiement prof" type, or null when it isn't seeded. */
    /**
     * La phrase lisible de l'annulation, prise dans la dernière ligne
     * marquée de la note (AnnulerDepense l'y ajoute en la préfixant du
     * marqueur). La note reste la source unique : rien n'est stocké deux
     * fois, donc rien ne peut diverger.
     */
    private static function motifAnnulation(Depense $depense): ?string
    {
        foreach (array_reverse(explode("
", (string) $depense->note)) as $ligne) {
            if (str_contains($ligne, Depense::MARQUEUR_ANNULE)) {
                return trim(str_replace(Depense::MARQUEUR_ANNULE, '', $ligne));
            }
        }

        return null;
    }

    /**
     * Le NOM du centre auquel la dépense est imputée.
     *
     * Résolu via `Depense::centreId()` — forme PHP de
     * `VentilationCentre::scopeDepensesAuCentre()`, la règle qui a filtré la
     * liste : groupe, sinon centre de saisie, sinon caisse. Les trois
     * relations (`group`, `etablissement`, `caisse.etablissement`) sont
     * eager-loadées par `__invoke()`, donc aucune requête par ligne (§17
     * perf : un read-model n'appelle jamais un accesseur par ligne).
     */
    private static function centreNom(Depense $depense): ?string
    {
        $id = $depense->centreId();

        if ($id === null) {
            return null;
        }

        return $depense->group?->etablissement?->nom_centre
            ?? $depense->etablissement?->nom_centre
            ?? $depense->caisse?->etablissement?->nom_centre;
    }

    public function paiementProfTypeId(): ?int
    {
        return TypeDepense::query()
            ->where('nom', TypeDepense::SYSTEM_PAIEMENT_PROF)
            ->value('id');
    }

    /**
     * @return Collection<int, array{id:int, nom:string}>
     */
    public function typeDepenseOptions(): Collection
    {
        return TypeDepense::query()
            ->where('statut', TypeDepense::STATUT_ACTIF)
            ->orderBy('nom')
            ->get()
            ->map(fn (TypeDepense $t): array => ['id' => $t->id, 'nom' => $t->nom]);
    }

    /**
     * Centre REACH (« Centres affectés », §16) — super-admins see every
     * centre; everyone else the centres they are assigned to, resolved with
     * the same rule as the active-centre filter above.
     */
    private function scopeToReachableCenters(Builder $query, User $user): void
    {
        if ($this->centerAccess->hasGlobalAccess($user)) {
            return;
        }

        $this->ventilation->scopeDepensesAuxCentres(
            $query,
            $this->centerAccess->accessibleCenterIds($user),
            true,
        );
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
