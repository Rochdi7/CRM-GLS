<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Activity;
use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Encaissement;
use App\Models\Remboursement;
use App\Services\Authorization\CenterAccessService;
use App\Services\CaisseProvisioner;
use App\Services\Context\CurrentContext;
use App\Support\Access\HiddenAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-model for the unified till journal ("Ma caisse" / "Journal des
 * transactions" tabs) — extracted verbatim from CaisseJournal::render()
 * (docs/phase-10-finance-mapping.md Q4: ported as-is, preserving the exact
 * current PHP-merge/sort/slice() pagination semantics — the confirmed
 * performance bottleneck is preserved, not fixed, in this phase; see
 * PERFORMANCE_AUDIT.md/PERFORMANCE_OPTIMIZATION_REPORT.md).
 *
 * Merges 4 money trails (encaissements, depenses, remboursements,
 * transferts) into one chronological Collection per till scope
 * ('mine'|'all'), same as the Livewire component's own `rows()`/`render()`.
 *
 * Since the payment-method accounts refactor (24/08/2026) an employee's
 * till holds ESPÈCES only, so every header figure of the 'mine' scope is a
 * cash figure by construction (« Solde espèces »). Non-cash money lives in
 * the centre's TPE/Chèque/Virement accounts, shown on the « Caisse globale »
 * tab (GetCaisseGlobale) — never folded into the cash solde.
 */
final class GetCaisseJournal
{
    public const PER_PAGE = 10;

    public const TYPE_PAIEMENT = 'paiement';

    public const TYPE_DEPENSE = 'depense';

    public const TYPE_REMBOURSEMENT = 'remboursement';

    public const TYPE_TRANSFERT = 'transfert';

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
        private readonly CaisseProvisioner $provisioner,
    ) {}

    /**
     * @return array{
     *     caissesInScope: Collection<int, array{id:int, nom:string}>,
     *     totalEncaissements: string, encaissementsParMethode: array<string, string>,
     *     totalDepenses: string, solde: string,
     *     totauxParType: array<string, string>, total: int, lastPage: int,
     *     page: int, rows: Collection<int, array<string, mixed>>,
     * }
     */
    public function __invoke(
        \App\Models\User $user,
        string $scope,
        string $typeFilter,
        string $dateFrom,
        string $dateTo,
        int $page,
    ): array {
        $scope = $scope === 'all' ? 'all' : 'mine';

        // Year switcher: the active year is the DEFAULT date window for the
        // journal ROWS — exactly as if the user had typed those dates, so an
        // explicit date filter takes over. The header figures
        // (totalEncaissements/totalDepenses/totalRemboursements/solde) stay
        // all-time on purpose: they must keep reconciling with the till's
        // running balance, which spans years.
        if ($dateFrom === '' && $dateTo === '' && ($range = $this->context->anneeDateRange()) !== null) {
            [$dateFrom, $dateTo] = $range;
        }

        if ($scope === 'mine') {
            $employee = $user->employee;

            if ($employee !== null) {
                $this->provisioner->provisionFor($employee);
            }
        }

        $ids = $this->caisseIds($user, $scope);

        // Only rows that actually MOVED the till are counted — the journal
        // must reconcile with `solde`:
        //  - an "apply" row (applied_from_encaissement_id) reallocates an
        //    avance already counted once (AppliquerAvance never credits);
        //  - a pending/refused dépense never debited anything (approval flow);
        //  - a pending/cancelled transfer never moved money (validation does).
        // « Encaissements » = everything the till owner(s) COLLECTED, whatever
        // the method (24/08/2026): a TPE/chèque/virement payment recorded by
        // the cashier is their work even though it lands in the centre's
        // method account, not their till. Broken down per method so the
        // cash part (the only one inside `solde`) stays visible.
        $agentIds = Caisse::query()->whereIn('id', $ids)->whereNotNull('responsable_employee_id')->pluck('responsable_employee_id');
        $parMethode = Encaissement::query()
            ->selectRaw('methode, sum(montant) as total')
            ->whereNull('applied_from_encaissement_id')
            ->where(fn ($q) => $q->whereIn('caisse_id', $ids)->orWhereIn('agent_id', $agentIds))
            // Ventilation par centre : une caissière encaisse pour plusieurs
            // centres avec une seule caisse, donc le KPI suit le centre du
            // PAIEMENT, pas l'étiquette de la caisse (cf. soldeVentile()).
            ->tap(fn ($q) => $this->scopeRecordsToActiveCenter($q))
            ->groupBy('methode')
            ->pluck('total', 'methode');
        $encaissementsParMethode = collect(Encaissement::METHODES)
            ->mapWithKeys(fn (string $m) => [$m => number_format((float) ($parMethode[$m] ?? 0), 2, '.', '')])
            ->all();
        $totalEncaissements = (float) $parMethode->sum();
        $depenseIdsDuCentre = $this->idsDuCentreDepuisLeLedger(Depense::class, $ids);
        $totalDepenses = (float) Depense::query()
            ->whereIn('caisse_id', $ids)
            ->where('statut', Depense::STATUT_APPROUVEE)
            ->when($depenseIdsDuCentre !== null, fn ($q) => $q->whereIn('id', $depenseIdsDuCentre))
            ->sum('montant');
        // Refunds decrement the till exactly like a dépense
        // (EnregistrerRemboursement), but they are their own outflow and must
        // not be folded into the "Dépenses" KPI — without this total the page
        // contradicts itself: the solde drops while every displayed total
        // stays put, which reads as "the caisse didn't record the refund".
        // ⚠ Les remboursements ANNULÉS sont exclus : leur caisse a été
        // recréditée par une écriture compensatoire, donc aucun argent n'est
        // sorti. Les compter faisait afficher 600 DH pour un remboursement de
        // 300 DH réellement remis (03/09/2026) — et le total contredisait
        // alors le solde, qui lui est juste.
        $totalRemboursements = (float) Remboursement::query()
            ->whereIn('caisse_id', $ids)
            ->where(fn ($q) => $this->exclureAnnules($q))
            ->tap(fn ($q) => $this->scopeRecordsToActiveCenter($q))
            ->sum('montant');
        $solde = $this->soldeVentile($ids, $depenseIdsDuCentre);

        $rows = $this->rows($ids, $typeFilter, $dateFrom, $dateTo, $depenseIdsDuCentre);

        $totauxParType = $rows->groupBy('type')->map(fn ($g) => number_format((float) $g->sum('montant'), 2, '.', ''));

        $lastPage = max(1, (int) ceil($rows->count() / self::PER_PAGE));
        $page = min(max(1, $page), $lastPage);

        return [
            'caissesInScope' => Caisse::query()->whereIn('id', $ids)->with('responsable')->get()
                ->map(fn (Caisse $c): array => ['id' => $c->id, 'nom' => $c->nom]),
            'totalEncaissements' => number_format($totalEncaissements, 2, '.', ''),
            'encaissementsParMethode' => $encaissementsParMethode,
            'totalDepenses' => number_format($totalDepenses, 2, '.', ''),
            'totalRemboursements' => number_format($totalRemboursements, 2, '.', ''),
            'solde' => number_format($solde, 2, '.', ''),
            'totauxParType' => $totauxParType->all(),
            'total' => $rows->count(),
            'lastPage' => $lastPage,
            'page' => $page,
            'rows' => $rows->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values()->map(fn (array $row): array => [
                ...$row,
                'montant' => number_format((float) $row['montant'], 2, '.', ''),
                'date' => $row['date']?->format('d/m/Y'),
            ]),
        ];
    }

    /**
     * Solde du CENTRE ACTIF pour les caisses données — pas le solde entier.
     *
     * Une caissière n'a qu'UNE caisse à vie (CLAUDE.md §11,
     * `caisses_une_caissiere_par_employe`, multi-caisses REJETÉ le
     * 01/09/2026), mais elle encaisse pour plusieurs centres : la caisse
     * d'Hafssa Elkhattabi est étiquetée GLS Rabat et porte pourtant des
     * paiements GLS Online. Filtrer sur `caisses.etablissement_id` ferait
     * basculer la caisse EN BLOC — tout sur Rabat, 0,00 DH sur Online où
     * l'argent dort pourtant.
     *
     * ⚠ La ventilation se lit sur les MÊMES colonnes que les lignes du
     * journal, jamais sur une autre source. Une première version dérivait le
     * solde du ledger (`properties->etablissement_id`) pendant que les lignes
     * filtraient sur `encaissements.etablissement_id` : les deux moitiés de
     * l'écran comptaient deux choses différentes, et l'onglet affichait
     * « 2 transactions, 500 DH » au-dessus d'un solde à 0,00 DH (04/09/2026,
     * caisse #10 — 154 écritures de ledger, aucune portant le centre 7, alors
     * que les encaissements le portent bien). Un écran dont le total et les
     * lignes ne lisent pas la même colonne se contredira toujours.
     *
     * `caisses.solde` reste l'autorité : la somme des parts de tous les
     * centres doit retomber dessus, ce que vérifie
     * `CaisseVentilationCentreTest`.
     *
     * Sans centre actif (« Tous les centres ») le solde entier est rendu tel
     * quel : rien n'est ventilé, donc rien n'est masqué.
     *
     * @param  array<int, int>  $ids
     * @param  array<int, int>|null  $depenseIdsDuCentre  dépenses du centre (le ledger reste leur seule dimension)
     */
    private function soldeVentile(array $ids, ?array $depenseIdsDuCentre): float
    {
        $centreId = $this->context->etablissementId();

        if ($centreId === null || $ids === []) {
            return (float) Caisse::query()->whereIn('id', $ids)->sum('solde');
        }

        // Espèces uniquement : le solde d'une caisse physique est un solde
        // espèces par construction depuis les comptes de méthode (24/08/2026).
        $entrees = (float) Encaissement::query()
            ->whereIn('caisse_id', $ids)
            ->whereNull('applied_from_encaissement_id')
            ->where('methode', Encaissement::METHODE_ESPECES)
            ->where('etablissement_id', $centreId)
            ->sum('montant');

        $sorties = (float) Depense::query()
            ->whereIn('caisse_id', $ids)
            ->where('statut', Depense::STATUT_APPROUVEE)
            ->when($depenseIdsDuCentre !== null, fn ($q) => $q->whereIn('id', $depenseIdsDuCentre))
            ->sum('montant');

        $sorties += (float) Remboursement::query()
            ->whereIn('caisse_id', $ids)
            ->where(fn ($q) => $this->exclureAnnules($q))
            ->where('etablissement_id', $centreId)
            ->sum('montant');

        // Les transferts validés déplacent réellement l'argent entre caisses :
        // les ignorer ferait diverger la somme des parts du solde stocké.
        $transfertIds = $this->idsDuCentreDepuisLeLedger(CaisseTransfer::class, $ids);
        $transferts = CaisseTransfer::query()
            ->where(fn ($q) => $q->whereIn('caisse_source_id', $ids)->orWhereIn('caisse_destination_id', $ids))
            ->where('statut', CaisseTransfer::STATUT_VALIDE)
            ->when($transfertIds !== null, fn ($q) => $q->whereIn('id', $transfertIds))
            ->get(['caisse_source_id', 'montant']);

        foreach ($transferts as $transfert) {
            $sorties += in_array($transfert->caisse_source_id, $ids, true)
                ? (float) $transfert->montant
                : -(float) $transfert->montant;
        }

        return round($entrees - $sorties, 2);
    }

    /**
     * Ids des enregistrements d'un modèle qui appartiennent au centre actif,
     * d'après le LEDGER.
     *
     * `depenses` et `caisse_transfers` ne portent aucune colonne
     * `etablissement_id` (vérifié en base le 04/09/2026) : leur centre n'existe
     * que dans les propriétés jsonb de l'écriture de caisse (§11 « Centre
     * dimension on the ledger »). C'est donc la seule façon de les ventiler.
     *
     * Retourne `null` quand aucun centre n'est actif — l'appelant ne filtre
     * alors rien, au lieu de filtrer sur une liste vide qui masquerait tout.
     *
     * Le fallback de lecture reste le même que `soldeVentile()` : une écriture
     * historique sans la clé est rattachée au centre de sa caisse, jamais
     * réécrite.
     *
     * @param  array<int, int>  $caisseIds
     * @return array<int, int>|null
     */
    private function idsDuCentreDepuisLeLedger(string $modelClass, array $caisseIds): ?array
    {
        $centreId = $this->context->etablissementId();

        if ($centreId === null) {
            return null;
        }

        $centreDeLaCaisse = Caisse::query()->whereIn('id', $caisseIds)->pluck('etablissement_id', 'id');

        return Activity::query()
            ->where('log_name', 'caisse')
            ->where('event', 'solde_movement')
            ->where('subject_type', Caisse::class)
            ->whereIn('subject_id', $caisseIds)
            ->where('properties->origine_type', $modelClass)
            ->get(['subject_id', 'properties'])
            ->filter(function (Activity $entry) use ($centreId, $centreDeLaCaisse): bool {
                $etab = $entry->properties['etablissement_id']
                    ?? $centreDeLaCaisse[$entry->subject_id]
                    ?? null;

                return $etab !== null && (int) $etab === $centreId;
            })
            ->pluck('properties.origine_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Restreint une requête au centre actif via la colonne `etablissement_id`
     * que porte la table elle-même.
     *
     * Vaut pour `encaissements` et `remboursements` uniquement : `depenses` et
     * `caisse_transfers` n'ont PAS cette colonne (vérifié en base le
     * 04/09/2026), leur centre ne vit que dans le ledger.
     */
    private function scopeRecordsToActiveCenter($query): void
    {
        $centreId = $this->context->etablissementId();

        if ($centreId === null) {
            return;
        }

        $query->where('etablissement_id', $centreId);
    }

    /** @return array<int, int> */
    private function caisseIds(\App\Models\User $user, string $scope): array
    {
        if ($scope === 'mine') {
            $employee = $user->employee;

            return $employee === null ? [] : $employee->caisses()->pluck('id')->all();
        }

        return Caisse::query()
            // The maintainer's till is out of scope for everyone but himself
            // (HiddenAccount): this is the single funnel feeding the journal's
            // « Caisse » dropdown, its header totals and its rows, so filtering
            // here keeps all three consistent. Nothing is lost — that till
            // holds no money and no record; were it ever to hold one, the row
            // would be missing from a total, so re-check before widening.
            ->tap(fn ($q) => HiddenAccount::hideCaisses($q))
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            ->tap(fn ($q) => $this->scopeToActiveCenter($q))
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int, int>  $ids
     */
    /**
     * Écarte les remboursements annulés par écriture compensatoire.
     *
     * Le marqueur vit sur le modèle (Remboursement::MARQUEUR_ANNULE), partagé
     * avec la commande de correction et avec GetRemboursementsList, pour que
     * les trois ne puissent pas diverger : un écran qui compte un
     * remboursement annulé affiche de l'argent qui n'est jamais sorti.
     */
    private function exclureAnnules($query): void
    {
        $query->whereNull('note')
            ->orWhere('note', 'not ilike', '%'.Remboursement::MARQUEUR_ANNULE.'%');
    }

    private function rows(array $ids, string $typeFilter, string $dateFrom, string $dateTo, ?array $depenseIdsDuCentre = null): Collection
    {
        $rows = collect();
        $wants = fn (string $type): bool => $typeFilter === '' || $typeFilter === $type;

        if ($wants(self::TYPE_PAIEMENT)) {
            $rows = $rows->concat(
                Encaissement::query()->with(['student', 'agent', 'etablissement'])
                    ->whereIn('caisse_id', $ids)
                    ->whereNull('applied_from_encaissement_id')
                    ->tap(fn ($q) => $this->scopeRecordsToActiveCenter($q))
                    ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_paiement', '>=', $dateFrom))
                    ->when($dateTo !== '', fn ($q) => $q->whereDate('date_paiement', '<=', $dateTo))
                    ->get()
                    ->map(fn ($e) => [
                        'type' => self::TYPE_PAIEMENT,
                        'reference' => $e->reference,
                        'libelle' => __('Payment received'),
                        'tiers' => $e->student?->nomComplet(),
                        'montant' => (float) $e->montant,
                        'sens' => 1,
                        'date' => $e->date_paiement,
                        'note' => $e->note,
                        'agent' => $e->agent?->nomComplet(),
                        'centre' => $e->etablissement?->nom_centre,
                        'url' => route('backoffice.encaissements.show', $e),
                    ]),
            );
        }

        if ($wants(self::TYPE_DEPENSE)) {
            $rows = $rows->concat(
                Depense::query()->with(['typeDepense', 'agent', 'group.etablissement'])
                    ->whereIn('caisse_id', $ids)
                    ->where('statut', Depense::STATUT_APPROUVEE)
                    ->when($depenseIdsDuCentre !== null, fn ($q) => $q->whereIn('id', $depenseIdsDuCentre))
                    ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_depense', '>=', $dateFrom))
                    ->when($dateTo !== '', fn ($q) => $q->whereDate('date_depense', '<=', $dateTo))
                    ->get()
                    ->map(fn ($d) => [
                        'type' => self::TYPE_DEPENSE,
                        'reference' => $d->reference,
                        'libelle' => $d->typeDepense?->nom ?? __('Expense'),
                        'tiers' => $d->description,
                        'montant' => (float) $d->montant,
                        'sens' => -1,
                        'date' => $d->date_depense,
                        'note' => $d->note,
                        'agent' => $d->agent?->nomComplet(),
                        // `depenses` ne porte pas de colonne centre : celui du
                        // groupe quand il y en a un (Paiement prof), sinon
                        // celui de la caisse qui a payé.
                        'centre' => $d->group?->etablissement?->nom_centre
                            ?? $d->caisse?->etablissement?->nom_centre,
                        'url' => route('backoffice.depenses.show', $d),
                    ]),
            );
        }

        if ($wants(self::TYPE_REMBOURSEMENT)) {
            $rows = $rows->concat(
                Remboursement::query()->with(['beneficiaire', 'agent', 'etablissement'])
                    ->whereIn('caisse_id', $ids)
                    ->tap(fn ($q) => $this->scopeRecordsToActiveCenter($q))
                    // Même règle que le total ci-dessus : un remboursement
                    // annulé n'a pas bougé la caisse, il n'a rien à faire
                    // dans le journal des mouvements.
                    ->where(fn ($q) => $this->exclureAnnules($q))
                    ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_remboursement', '>=', $dateFrom))
                    ->when($dateTo !== '', fn ($q) => $q->whereDate('date_remboursement', '<=', $dateTo))
                    ->get()
                    ->map(fn ($r) => [
                        'type' => self::TYPE_REMBOURSEMENT,
                        'reference' => $r->reference,
                        'libelle' => $r->motif ?: __('Refund'),
                        'tiers' => $r->beneficiaire?->nomComplet(),
                        'montant' => (float) $r->montant,
                        'sens' => -1,
                        'date' => $r->date_remboursement,
                        'note' => $r->note,
                        'agent' => $r->agent?->nomComplet(),
                        'centre' => $r->etablissement?->nom_centre,
                        // No detail page exists anywhere for Remboursements
                        // (docs/phase-10-finance-mapping.md Q2: preserved).
                        'url' => null,
                    ]),
            );
        }

        if ($wants(self::TYPE_TRANSFERT)) {
            $rows = $rows->concat(
                CaisseTransfer::query()->with(['caisseSource.etablissement', 'caisseDestination.etablissement', 'requestedBy'])
                    ->where(fn ($q) => $q->whereIn('caisse_source_id', $ids)->orWhereIn('caisse_destination_id', $ids))
                    ->where('statut', CaisseTransfer::STATUT_VALIDE)
                    // Un transfert inter-centres est un mouvement de centre
                    // EXPLICITE et journalisé (§11) : chaque jambe estampille
                    // le centre de SA caisse, donc il reste visible des deux
                    // côtés — masquer une jambe ferait disparaître de l'argent
                    // qui a réellement bougé.
                    ->when(
                        ($transfertIds = $this->idsDuCentreDepuisLeLedger(CaisseTransfer::class, $ids)) !== null,
                        fn ($q) => $q->whereIn('id', $transfertIds),
                    )
                    ->when($dateFrom !== '', fn ($q) => $q->whereDate('date_transfert', '>=', $dateFrom))
                    ->when($dateTo !== '', fn ($q) => $q->whereDate('date_transfert', '<=', $dateTo))
                    ->get()
                    ->map(fn ($t) => [
                        'type' => self::TYPE_TRANSFERT,
                        'reference' => $t->reference,
                        'libelle' => ($t->caisseSource?->nom ?? '—').' → '.($t->caisseDestination?->nom ?? '—'),
                        'tiers' => $t->statut,
                        'montant' => (float) $t->montant,
                        'sens' => in_array($t->caisse_source_id, $ids, true) ? -1 : 1,
                        'date' => $t->date_transfert,
                        'note' => $t->note,
                        'agent' => $t->requestedBy?->nomComplet(),
                        // Le centre de la jambe vue d'ici : source si la caisse
                        // débitée est dans le scope, destination sinon.
                        'centre' => in_array($t->caisse_source_id, $ids, true)
                            ? $t->caisseSource?->etablissement?->nom_centre
                            : $t->caisseDestination?->etablissement?->nom_centre,
                        'url' => route('backoffice.caisse-transfers.show', $t),
                    ]),
            );
        }

        return $rows->sortByDesc(fn ($row) => $row['date']?->timestamp ?? 0)->values();
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
