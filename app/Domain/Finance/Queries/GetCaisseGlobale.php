<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Caisse;
use App\Models\CaisseTransfer;
use App\Models\Depense;
use App\Models\Encaissement;
use App\Models\Remboursement;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
use App\Domain\Finance\Support\VentilationCentre;
use App\Services\Context\CurrentContext;
use App\Support\Access\DormantTill;
use App\Support\Access\HiddenAccount;

/**
 * « Caisse globale » tab of Gestion de la caisse — where the money of the
 * active centre is, one card per kind of account and, under each card, the
 * accounts of that kind with their stored (CaisseLedger-kept) solde:
 *
 *   Caisse personnelle  = the employees' physical tills (Caissière)
 *   Caisse TPE          = the centre's card account
 *   Caisse bancaire     = the centre's Virement account
 *   Caisse chèque       = the centre's Chèque account
 *
 * Every dirham appears once: the figures are the accounts' own balances,
 * nothing is derived on top (CLAUDE.md §11). Scope = the centres the user
 * may reach, narrowed to the active centre — same rule as the 'all' journal
 * (« Tous les centres », super-admin only, lifts the narrowing and shows
 * every reachable centre's accounts).
 *
 * Dormant personal tills are hidden — see App\Support\Access\DormantTill,
 * shared with « Comptes de caisse »: a teacher's or a non-Actif employee's
 * till at exactly 0.00 DH is noise, but one still holding money stays listed
 * so no dirham drops out of the totals.
 *
 * ⚠ The optional date window REWINDS the balances, it does not sum a period
 * (03/09/2026). This screen answers « combien y a-t-il en caisse », a figure
 * that must stay reconcilable with the physical cash, so with a `dateTo` set
 * every account shows the solde it HELD at the end of that day: its stored
 * solde rebuilt from the money records dated up to and including it. Showing
 * the period's entrées − sorties instead would print 0.00 DH for a till holding
 * 104 450 DH that simply had a quiet month — a dangerous number on a screen
 * people use to check tills. `dateFrom` therefore narrows nothing on its own;
 * it is only the readable half of the window the rest of the page filters on
 * (and it is what an « Entrées/Sorties de la période » column would use, if
 * one is ever added).
 *
 * The rewind is a handful of GROUP BY aggregates over the source tables,
 * never one query per account.
 *
 * ⚠ It rebuilds the balance from the money records themselves —
 * encaissements, dépenses approuvées, remboursements, transferts validés —
 * on their BUSINESS dates, NOT from the journal's write timestamps. Rewinding
 * by the journal moved the physical tills only (09/09/2026): the TPE /
 * Virement / Chèque accounts hold almost no `solde_movement` entries, so
 * subtracting « what was journalled after that day » subtracted nothing and
 * their cards kept printing TODAY's solde under a PAST date. Three of the four
 * cards silently ignored the filter. See soldesAt().
 *
 * ⚠ The rewind is only meaningful BACK TO the first money record
 * (`journalDepuis`, the oldest `date_paiement`). Before it nothing had been
 * received, so a balance there is not a fact the data can state; the horizon
 * is returned to the page, which refuses to draw a balance older than it and
 * says why instead of showing a false zero (03/09/2026). Never let this fall
 * back to « 0.00 DH » silently: an unknown balance and an empty till are not
 * the same statement.
 */
final class GetCaisseGlobale
{
    /**
     * Display order + French card labels, keyed by Caisse type.
     *
     * « Caisse externe » is deliberately NOT listed for now (GLS does not use
     * external cash accounts yet — 24/08/2026); add
     * `Caisse::TYPE_EXTERNE => 'Caisse externe'` here when they do. Externe
     * rows still exist and stay visible on « Comptes de caisse ».
     */
    public const LABELS = [
        Caisse::TYPE_CAISSIERE => 'Caisse personnelle',
        Caisse::TYPE_TPE => 'Caisse TPE',
        Caisse::TYPE_VIREMENT => 'Caisse bancaire',
        Caisse::TYPE_CHEQUE => 'Caisse chèque',
    ];

    public function __construct(
        private readonly CenterAccessService $centerAccess,
        private readonly CurrentContext $context,
        private readonly VentilationCentre $ventilation,
    ) {}

    /**
     * @param  array{dateFrom?: string, dateTo?: string}  $filters
     * @return array{
     *     cards: list<array{type: string, label: string, total: string, count: int}>,
     *     comptes: array<string, list<array{id: int, nom: string, centre: string|null, responsable: string|null, solde: string, showUrl: string}>>,
     *     total: string,
     *     asOf: string|null,
     *     journalDepuis: string|null,
     *     avantJournal: bool,
     * }
     */
    public function __invoke(User $user, array $filters = []): array
    {
        $dateTo = trim((string) ($filters['dateTo'] ?? ''));
        $dateTo = $dateTo !== '' ? $dateTo : null;

        // The day the journal starts: before it, a rewind cannot know what a
        // caisse held, and must say so rather than print 0.00 DH.
        $journalDepuis = $this->journalDepuis();
        $avantJournal = $dateTo !== null
            && $journalDepuis !== null
            && $dateTo < $journalDepuis;

        $caisses = Caisse::query()
            ->with(['etablissement', 'responsable'])
            ->tap(fn ($q) => HiddenAccount::hideCaisses($q))
            ->tap(fn ($q) => DormantTill::hide($q))
            ->tap(fn ($q) => $this->centerAccess->scopeAccessibleCenters($q, $user))
            // ⚠ Même règle que « Comptes de caisse » : une caisse appartient
            // à un centre par rattachement, par AFFECTATION de son
            // responsable (§16) ou parce qu'elle y détient de l'argent.
            // Filtrer sur le seul `caisses.etablissement_id` faisait basculer
            // la caisse EN BLOC et masquait les caissières dont ce centre
            // n'est que secondaire (VentilationCentre).
            ->tap(fn ($q) => $this->ventilation->scopeCaissesDuCentre($q, $this->context->etablissementId()))
            ->orderByDesc('solde')
            ->orderBy('nom')
            ->get();

        // Part du centre actif, dérivée des mêmes tables que les écrans de
        // détail (VentilationCentre) : sans elle, sélectionner un centre
        // affichait la TOTALITÉ de chaque caisse, y compris l'argent d'un
        // autre centre. Sur « Tous les centres » rien n'est ventilé.
        $centreId = $this->context->etablissementId();

        // Balances as they stood at the end of $dateTo (no date ⇒ the stored
        // soldes, i.e. the unfiltered screen), VENTILÉES sur le centre actif
        // comme la vue courante — voir soldesAt().
        $soldes = $avantJournal
            ? []
            : $this->soldesAt($caisses->all(), $dateTo, $centreId);

        foreach ($caisses as $caisse) {
            $solde = $soldes[$caisse->id] ?? (float) $caisse->solde;

            // Rembobinage ET ventilation : un écran qui NOMME un centre ne
            // doit jamais afficher l'argent d'un autre, date ou pas. Sans
            // date, la part vient de VentilationCentre (source unique,
            // partagée avec « Comptes de caisse ») ; avec une date, soldesAt()
            // rebâtit cette MÊME part sur les mêmes colonnes, à la date
            // demandée. L'ancienne version rendait la main au solde ENTIER dès
            // qu'une date était posée : la page listait les caisses d'un
            // centre en annonçant les montants du réseau, et contredisait
            // « Comptes de caisse » au même instant (§11).
            if ($centreId !== null && $dateTo === null) {
                $solde = $this->ventilation->soldeDuCentre($caisse, $centreId);
            }

            $caisse->solde = number_format($solde, 2, '.', '');
        }

        // Re-sorted AFTER the rewind: ordering on today's solde would list a
        // past state in an order the numbers on screen don't explain.
        $caisses = $caisses
            ->sortBy(fn (Caisse $c): string => $c->nom)
            ->sortByDesc(fn (Caisse $c): float => (float) $c->solde)
            ->values();

        $byType = $caisses->groupBy('type');
        $cards = [];
        $comptes = [];

        foreach (self::LABELS as $type => $label) {
            $group = $byType->get($type, collect());

            $cards[] = [
                'type' => $type,
                'label' => $label,
                'total' => $this->money((float) $group->sum('solde')),
                'count' => $group->count(),
            ];

            $comptes[$type] = $group->map(fn (Caisse $c): array => [
                'id' => $c->id,
                'nom' => $c->nom,
                'centre' => $c->etablissement?->nom_centre,
                'responsable' => $c->responsable?->nomComplet(),
                'solde' => $this->money((float) $c->solde),
                'showUrl' => route('backoffice.caisses.show', $c),
            ])->values()->all();
        }

        return [
            'cards' => $cards,
            'comptes' => $comptes,
            // Σ of the listed kinds only (an unlisted Externe account is not
            // silently folded into a total the cards don't show).
            'total' => $this->money((float) $caisses->whereIn('type', array_keys(self::LABELS))->sum('solde')),
            // Echoed back so the page can label the figures as a past state
            // rather than letting them read as today's cash.
            'asOf' => $avantJournal ? null : $dateTo,
            // First day the journal can answer for, and whether the requested
            // date falls before it (⇒ the figures shown are today's stored
            // soldes, not a rewind — the page says so).
            'journalDepuis' => $journalDepuis,
            'avantJournal' => $avantJournal,
        ];
    }

    /**
     * Day of the OLDEST money record (yyyy-mm-dd), or null when there is
     * none — the first day a rewind can answer for.
     *
     * ⚠ Derived from the SOURCE tables' business dates, not from the
     * journal's `created_at`. The journal is a write log: the legacy import
     * wrote 23 437 of its 23 810 entries on 26/08/2026 whatever each
     * payment's real `date_paiement`, so dating the rewind by it made every
     * balance before that day unknowable — including for accounts whose
     * money demonstrably predates it.
     */
    private function journalDepuis(): ?string
    {
        $premier = Encaissement::query()->min('date_paiement');

        return $premier === null ? null : substr((string) $premier, 0, 10);
    }

    /**
     * Balance of each caisse at the END of $dateTo, restricted to $centreId
     * when a centre is active. Empty when no date is given, so the caller
     * keeps the stored soldes untouched.
     *
     * ⚠ Rebuilt from the SOURCE tables (encaissements, dépenses approuvées,
     * remboursements, transferts validés) on their BUSINESS dates —
     * `date_paiement`, `date_depense`, `date_remboursement`,
     * `date_transfert` — never from the journal's `created_at`.
     *
     * Two bugs this shape fixes at once (09/09/2026), both from the previous
     * version subtracting `solde_movement` entries:
     *
     *  1. **Only « Caisse personnelle » moved.** The journal covers the
     *     physical tills almost exclusively (23 810 entries) and the
     *     TPE / Virement / Chèque accounts barely at all, because their
     *     balances were re-homed by `caisse:recalculer-soldes` rather than
     *     accumulated movement by movement. Σ(movements after $dateTo) was
     *     therefore ~0 for those three, and their card printed today's
     *     stored solde whatever date the user picked — a past date silently
     *     answering with a present figure, on the one screen used to check
     *     where the money is.
     *  2. **Even the tills rewound by the wrong day**, since the import's
     *     write date is not the payment's date.
     *
     * ⚠ Et il ventile (11/09/2026). Une caissière n'a qu'UNE caisse à vie
     * mais encaisse pour plusieurs centres (§11) : rembobiner sans borne de
     * centre rendait le solde RÉSEAU de chaque tiroir sous une liste qui ne
     * montrait que les caisses d'un centre — « Caisse globale » annonçait
     * 2 843 190,00 DH là où « Comptes de caisse » en annonçait une fraction,
     * AU MÊME INSTANT. Les colonnes de centre sont donc exactement celles de
     * VentilationCentre (`encaissements.etablissement_id`,
     * `remboursements.etablissement_id`, dépenses résolues par groupe puis
     * par caisse, transfert imputé au centre du mouvement) : un total et les
     * lignes qu'il chapeaute ne lisent jamais deux sources différentes.
     *
     * @param  list<Caisse>  $caisses
     * @return array<int, float>
     */
    private function soldesAt(array $caisses, ?string $dateTo, ?int $centreId): array
    {
        if ($dateTo === null || $caisses === []) {
            return [];
        }

        $caisseIds = array_map(static fn (Caisse $c): int => $c->id, $caisses);

        // Entrées — encaissements. An application row moved no money (the
        // avance it draws on was credited when it was received), so it is
        // excluded here exactly as VentilationCentre excludes it.
        $entrees = Encaissement::query()
            ->whereIn('caisse_id', $caisseIds)
            ->whereNull('applied_from_encaissement_id')
            ->when($centreId !== null, fn ($q) => $q->where('etablissement_id', $centreId))
            ->whereDate('date_paiement', '<=', $dateTo)
            ->groupBy('caisse_id')
            ->selectRaw('caisse_id, SUM(montant) AS total')
            ->pluck('total', 'caisse_id');

        // Sorties — dépenses APPROUVÉES only (a pending one never debited).
        $depenses = Depense::query()
            ->whereIn('caisse_id', $caisseIds)
            ->where('statut', Depense::STATUT_APPROUVEE)
            ->when($centreId !== null, fn ($q) => $this->ventilation->scopeDepensesAuCentre($q, $centreId))
            ->whereDate('date_depense', '<=', $dateTo)
            ->groupBy('caisse_id')
            ->selectRaw('caisse_id, SUM(montant) AS total')
            ->pluck('total', 'caisse_id');

        $remboursements = Remboursement::query()
            ->whereIn('caisse_id', $caisseIds)
            ->when($centreId !== null, fn ($q) => $q->where('etablissement_id', $centreId))
            ->whereDate('date_remboursement', '<=', $dateTo)
            ->groupBy('caisse_id')
            ->selectRaw('caisse_id, SUM(montant) AS total')
            ->pluck('total', 'caisse_id');

        $soldes = [];

        foreach ($caisseIds as $id) {
            $soldes[$id] = round(
                (float) ($entrees[$id] ?? 0)
                    - (float) ($depenses[$id] ?? 0)
                    - (float) ($remboursements[$id] ?? 0),
                2,
            );
        }

        // Transferts VALIDÉS — physical cash moved between two accounts, so
        // each leg is applied to its own side. Ignoring them would leave the
        // two tills of a transfer both reading as if it never happened.
        //
        // ⚠ Centre of a leg = VentilationCentre::centreDeLaJambe(), the ONE
        // implementation (11/09/2026): BOTH legs carry the centre the money
        // LEAVES. This block used to book the entry on the receiving till's
        // centre, so with a date set a Casablanca → Kénitra-till transfer
        // moved from Casablanca to Kénitra on the same screen the undated
        // view showed it on Casablanca. Never re-derive the rule here.
        $parId = [];

        foreach ($caisses as $caisse) {
            $parId[$caisse->id] = $caisse;
        }

        foreach (CaisseTransfer::query()
            ->where('statut', CaisseTransfer::STATUT_VALIDE)
            ->whereDate('date_transfert', '<=', $dateTo)
            ->where(fn ($q) => $q
                ->whereIn('caisse_source_id', $caisseIds)
                ->orWhereIn('caisse_destination_id', $caisseIds))
            ->with('caisseSource:id,etablissement_id')
            ->get(['caisse_source_id', 'caisse_destination_id', 'montant', 'etablissement_id']) as $transfert) {
            $montant = (float) $transfert->montant;

            foreach ([(int) $transfert->caisse_source_id => -$montant, (int) $transfert->caisse_destination_id => $montant] as $id => $delta) {
                if (! array_key_exists($id, $soldes)) {
                    continue;
                }

                if ($centreId !== null && $this->ventilation->centreDeLaJambe($transfert, $parId[$id]) !== $centreId) {
                    continue;
                }

                $soldes[$id] = round($soldes[$id] + $delta, 2);
            }
        }

        return $soldes;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
