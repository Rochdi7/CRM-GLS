<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Encaissement;
use App\Models\Remboursement;
use App\Domain\Finance\Support\VentilationCentre;
use App\Services\Context\CurrentContext;
use App\Support\Access\DormantTill;
use App\Support\Access\HiddenAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Read-model for the « Comptes de caisse » tab of Gestion de la caisse.
 *
 * Lists every account money sits in — ALL of them real `caisses` rows whose
 * balance is the stored, CaisseLedger-maintained `solde`:
 *
 *  - "Caissière" — an employee's own physical till (Espèces), created by
 *    CaisseProvisioner (EmployeeObserver), never by hand.
 *  - "Externe" — a safe / outside cash holder, the ONLY kind a user creates
 *    here.
 *  - "TPE" / "Chèque" / "Virement" — one account per centre, provisioned
 *    with the centre (EtablissementObserver) and credited only by payments
 *    carrying that method (CaisseResolver).
 *
 * A flat list, newest account first (like the legacy screen). Every dirham
 * is in exactly one row: nothing is derived on top of the stored balances
 * any more (the old DERIVED_TYPES aggregation counted a TPE payment twice —
 * once in the cashier's till, once in a live "TPE" line).
 *
 * ⚠ Follows the top-bar context like every other screen (CLAUDE.md §11,
 * « Context scoping is MANDATORY »): a centre selected in the switcher shows
 * THAT centre's accounts only — and, since 04/09/2026, that centre's SHARE of
 * each account, not the account's whole balance.
 *
 * Une caissière n'a qu'UNE caisse à vie (§11) mais encaisse pour plusieurs
 * centres : la caisse d'Hafssa Elkhattabi est étiquetée GLS Rabat et porte
 * pourtant des paiements GLS Online. Filtrer seulement sur
 * `caisses.etablissement_id` faisait basculer la caisse EN BLOC — Rabat
 * annonçait 112 550 DH encaissés / 103 900 DH de solde, Online n'en voyait
 * rien. Les colonnes ENCAISSEMENTS / DÉPENSES / SOLDE se ventilent donc par
 * centre, exactement comme GetCaisseJournal, et sur les MÊMES colonnes que
 * les écrans de détail (`encaissements.etablissement_id`…) pour qu'aucun
 * total ne puisse contredire la liste qu'il chapeaute.
 *
 * Sur « Tous les centres » rien n'est ventilé : les soldes stockés sont
 * rendus tels quels, et leur somme est le total réseau. The tab is super-admin only (`cash-accounts.*`
 * is absent from every role in PermissionRegistry::matrix()), and a
 * super-admin is exactly who may pick « Tous les centres » — that is the
 * global view of where the money is, not the default for a selected centre.
 *
 * Centre-less accounts (an Externe safe with no `etablissement_id`) are
 * global and stay visible in every centre, same rule as GetCaisseGlobale.
 */
final class GetComptesCaisse
{
    public const DEFAULT_PER_PAGE = 10;

    public function __construct(
        private readonly CurrentContext $context,
        private readonly VentilationCentre $ventilation,
    ) {}

    /** The only type a user may create by hand. */
    public const CREATABLE_TYPES = [Caisse::TYPE_EXTERNE];

    /**
     * Every type the filter dropdown offers, in display order.
     *
     * @return list<string>
     */
    public static function allTypes(): array
    {
        return [Caisse::TYPE_CAISSIERE, ...Caisse::TYPES_METHODE, Caisse::TYPE_EXTERNE];
    }

    public function __invoke(
        string $search = '',
        string $typeFilter = '',
        int $perPage = self::DEFAULT_PER_PAGE,
        int $page = 1,
    ): LengthAwarePaginator {
        $rows = $this->baseQuery($search, $typeFilter)
            ->latest()
            ->orderBy('nom')
            ->get()
            ->map(fn (Caisse $caisse): array => [
                'id' => $caisse->id,
                'nom' => $caisse->nom,
                'type' => $caisse->type,
                'centre' => $caisse->etablissement?->nom_centre,
                'responsable' => $caisse->responsable?->nomComplet(),
                'encaissements' => $this->money((float) ($caisse->encaissements_total ?? 0)),
                'depenses' => $this->money((float) ($caisse->depenses_total ?? 0)),
                // Sur « Tous les centres » : le solde stocké, autorité absolue
                // (CaisseLedger) — jamais recalculé depuis les deux totaux
                // ci-dessus, qui ignorent remboursements, transferts et solde
                // d'ouverture. Sur un centre : la part de CE centre, dérivée
                // des mêmes colonnes que les écrans de détail.
                'solde' => $this->money($this->soldeDuCentre($caisse)),
                'statut' => $caisse->statut,
                'dateAjout' => $caisse->created_at?->format('d/m/Y'),
                // Method accounts are provisioned, not managed: no edit/delete.
                'compteMethode' => $caisse->isCompteMethode(),
                'showUrl' => route('backoffice.caisses.show', $caisse),
            ]);

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values()->all(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => request()->query()],
        );
    }

    /**
     * Whether the account still carries movements — used to refuse a delete
     * (money records are never deleted, so neither is the account they hang
     * off; see the Finance invariants in CLAUDE.md §11).
     */
    public function hasMovements(Caisse $caisse): bool
    {
        return $caisse->encaissements()->exists()
            || $caisse->depenses()->exists()
            || $caisse->remboursements()->exists()
            || $caisse->transfersSortants()->exists()
            || $caisse->transfersEntrants()->exists();
    }

    /** @return Builder<Caisse> */
    private function baseQuery(string $search, string $typeFilter): Builder
    {
        return Caisse::query()
            ->with(['etablissement', 'responsable'])
            // Same rule as GetCaissesList: the maintainer's auto-provisioned
            // till is hidden along with the account (HiddenAccount).
            ->tap(fn ($q) => HiddenAccount::hideCaisses($q))
            // An empty till belonging to a teacher or a departed employee is
            // noise — one that still holds money is never hidden (DormantTill).
            ->tap(fn ($q) => DormantTill::hide($q))
            // Active centre from the top-bar switcher; null = « Tous les
            // centres » (super-admin only) ⇒ no narrowing. Les trois façons
            // dont une caisse appartient à un centre vivent dans
            // VentilationCentre, partagées avec « Caisse globale ».
            ->tap(fn ($q) => $this->ventilation->scopeCaissesDuCentre($q, $this->context->etablissementId()))
            // Both columns must count ONLY what actually moved the till, so
            // the row reconciles with the `solde` printed beside it — the
            // same two filters GetCaisseDetails and GetCaisseJournal apply:
            //  - an "apply" row (applied_from_encaissement_id) re-allocates
            //    an avance already counted once; AppliquerAvance credits
            //    nothing;
            //  - a pending or refused dépense debited nothing (approval flow,
            //    CLAUDE.md §11) — its money is still held in the till.
            // Reported 04/09/2026: Maria Nezha Jalloul's row announced
            // 8 960.00 DH spent while her own detail page correctly listed
            // « Aucune dépense » — the 4 rows behind it were all « En
            // attente ». The two screens must never disagree about what left.
            // …et ventilées par centre actif : une caisse porte l'argent de
            // plusieurs centres, la colonne doit montrer la part de celui
            // qu'on regarde (cf. le bloc de doc en tête de classe).
            ->withSum([
                'encaissements as encaissements_total' => fn ($q) => $q
                    ->whereNull('applied_from_encaissement_id')
                    ->tap(fn ($e) => $this->scopeToActiveCentre($e)),
            ], 'montant')
            ->withSum([
                'depenses as depenses_total' => fn ($q) => $q
                    ->where('statut', Depense::STATUT_APPROUVEE)
                    ->tap(fn ($d) => $this->scopeDepensesToActiveCentre($d)),
            ], 'montant')
            ->when($typeFilter !== '', fn ($q) => $q->where('type', $typeFilter))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('nom', 'ilike', "%{$search}%")
                    ->orWhereHas('etablissement', fn ($e) => $e->where('nom_centre', 'ilike', "%{$search}%"));
            }));
    }

    /** Restreint une relation portant sa propre colonne `etablissement_id`. */
    private function scopeToActiveCentre($query): void
    {
        $centreId = $this->context->etablissementId();

        if ($centreId !== null) {
            $query->where('etablissement_id', $centreId);
        }
    }

    /** Dépenses : pas de colonne centre, résolution partagée (§ VentilationCentre). */
    private function scopeDepensesToActiveCentre($query): void
    {
        $centreId = $this->context->etablissementId();

        if ($centreId !== null) {
            $this->ventilation->scopeDepensesAuCentre($query, $centreId);
        }
    }

    /** Part du centre actif dans le solde — source unique, voir VentilationCentre. */
    private function soldeDuCentre(Caisse $caisse): float
    {
        return $this->ventilation->soldeDuCentre($caisse, $this->context->etablissementId());
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
