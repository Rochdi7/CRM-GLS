<?php

declare(strict_types=1);

namespace App\Domain\Finance\Queries;

use App\Models\Activity;
use App\Models\Caisse;
use App\Models\User;
use App\Services\Authorization\CenterAccessService;
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
 * solde minus every CaisseLedger movement journalled after it. Showing the
 * period's entrées − sorties instead would print 0.00 DH for a till holding
 * 104 450 DH that simply had a quiet month — a dangerous number on a screen
 * people use to check tills. `dateFrom` therefore narrows nothing on its own;
 * it is only the readable half of the window the rest of the page filters on
 * (and it is what an « Entrées/Sorties de la période » column would use, if
 * one is ever added).
 *
 * The rewind is ONE aggregate query over the `solde_movement` entries, never
 * one query per account — the same journal `caisse:verifier-coherence`
 * reconciles the stored balances against.
 *
 * ⚠ The rewind is only meaningful BACK TO the first journal entry
 * (`journalDepuis`). CaisseLedger was introduced on 26/08/2026 and the legacy
 * import wrote its 23 809 movements on that day whatever a payment's real
 * `date_paiement`, so the journal knows nothing before it. Asking for
 * 28/02/2026 subtracts EVERY known movement and prints 0.00 DH — a till that
 * held money reading as empty, on the one screen used to check tills
 * (03/09/2026). So the horizon is returned to the page, which refuses to draw
 * a balance older than it and says why instead of showing a false zero. Never
 * let this fall back to « 0.00 DH » silently: an unknown balance and an empty
 * till are not the same statement.
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
            ->when($this->context->etablissementId(), fn ($q, $id) => $q
                ->where(fn ($w) => $w->whereNull('etablissement_id')->orWhere('etablissement_id', $id)))
            ->orderByDesc('solde')
            ->orderBy('nom')
            ->get();

        // Balances as they stood at the end of $dateTo (no date ⇒ the stored
        // soldes, i.e. the unfiltered screen).
        $soldes = $avantJournal
            ? []
            : $this->soldesAt($caisses->pluck('id')->all(), $dateTo);

        foreach ($caisses as $caisse) {
            $caisse->solde = number_format($soldes[$caisse->id] ?? (float) $caisse->solde, 2, '.', '');
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
     * Day of the OLDEST `solde_movement` entry (yyyy-mm-dd), or null when the
     * journal is empty. Everything before it is outside what the journal can
     * reconstruct — see the class docblock.
     */
    private function journalDepuis(): ?string
    {
        $premier = Activity::query()
            ->where('log_name', 'caisse')
            ->where('event', 'solde_movement')
            ->min('created_at');

        return $premier === null ? null : substr((string) $premier, 0, 10);
    }

    /**
     * Balance of each caisse at the END of $dateTo, rebuilt from the journal:
     * stored solde − Σ(movements journalled after that day). Empty when no
     * date is given, so the caller keeps the stored soldes untouched.
     *
     * @param  list<int>  $caisseIds
     * @return array<int, float>
     */
    private function soldesAt(array $caisseIds, ?string $dateTo): array
    {
        if ($dateTo === null || $caisseIds === []) {
            return [];
        }

        $posterieurs = Activity::query()
            ->where('log_name', 'caisse')
            ->where('event', 'solde_movement')
            ->whereIn('subject_id', $caisseIds)
            // Strictly after $dateTo, so a movement made ON that day is part
            // of the balance being shown.
            ->whereDate('created_at', '>', $dateTo)
            ->get(['subject_id', 'properties']);

        $aRembobiner = [];

        foreach ($posterieurs as $entry) {
            $properties = $entry->properties;
            $montant = (float) ($properties['montant'] ?? 0);
            $id = (int) $entry->subject_id;
            $aRembobiner[$id] = ($aRembobiner[$id] ?? 0.0)
                + (($properties['sens'] ?? '') === 'Entrée' ? $montant : -$montant);
        }

        $soldes = [];

        foreach (Caisse::query()->whereIn('id', $caisseIds)->get(['id', 'solde']) as $caisse) {
            $soldes[$caisse->id] = round((float) $caisse->solde - ($aRembobiner[$caisse->id] ?? 0.0), 2);
        }

        return $soldes;
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
