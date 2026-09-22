<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Domain\Finance\Support\VentilationCentre;
use App\Models\Depense;
use App\Models\Inscription;
use App\Services\Context\CurrentContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "Résumé des frais annuels" dashboard chart — one monthly point per month of
 * the ACTIVE ACADEMIC YEAR (the top-bar context switcher, 26/08/2026 — the
 * chart used to be a fixed calendar-year view with its own selector, which
 * split every school year across two calendar years and piled the imported
 * fees into one spike), 5 series (docs clarified 2026-08-14):
 *  - chiffreAffaire: the fees whose date_echeance falls in that month — the
 *    full InscriptionFee.montant on an « Active » inscription, but ONLY WHAT
 *    WAS PAID on a closed one (see « Un dossier clos ne doit rien » below);
 *  - collecte: Encaissement.montant received against those SAME fees
 *    (payments settling a fee due in that month, regardless of when paid);
 *  - resteAPayer: chiffreAffaire − collecte for that month;
 *  - depenses: Depense.montant for that month (by date_depense);
 *  - encaissements: ALL Encaissement.montant received in that calendar month
 *    (by date_paiement), regardless of which fee/month it settles — can
 *    exceed chiffreAffaire when a month collects on many prior months' fees
 *    at once (avances, late settlements).
 *
 * Center-scoped via CurrentContext (same active-center rule as
 * GetDashboardStats). When no année scolaire is selected (fresh session with
 * no default year), the current calendar year is the fallback window.
 *
 * ⚠ THE MONTH ALONE DECIDES WHICH POINT A ROW LANDS ON (21/09/2026). The
 * active année only supplies the WINDOW (its date_debut → date_fin); no
 * series is additionally filtered on `inscriptions.annee_scolaire_id`.
 * Crossing the two — « inscription of this année » AND « dated inside this
 * année » — drops every row that satisfies one half only, and such a row is
 * then shown by NO année's chart: a 2025/2026 group still running in
 * September 2026 bills fees due 09/2026, which the 2025/2026 chart refused on
 * the date and the 2026/2027 chart refused on the année — reported with
 * September 2026 reading 3 100 DH of chiffre d'affaire. Encaissements had the
 * same hole (an early payment on NEXT year's inscription, dated this year).
 * Années never overlap (CouvertureAnneesRules,
 * CLAUDE.md §11), so a date falls in exactly one window and nothing is counted
 * twice. Same rule as the dashboard's « ce mois-ci » cards.
 *
 * Performance (24/08/2026): every series is ONE PostgreSQL GROUP BY month
 * aggregate — 4 queries total, whatever the data volume. The previous
 * version hydrated every fee of the year and called
 * InscriptionFee::montantPaye() per row (one SUM query each), so the
 * dashboard cost grew linearly with the number of fees (thousands of
 * queries on a production centre). Never reintroduce per-row PHP loops here.
 */
final class GetAnnualFraisSummary
{
    public function __construct(
        private readonly CurrentContext $context,
        private readonly VentilationCentre $ventilation,
    ) {}

    /** The chart header label — the année scolaire the window covers. */
    public function periodeLabel(): string
    {
        return $this->context->anneeScolaire()?->nom ?? (string) now()->year;
    }

    /**
     * @return array{
     *     months: list<string>,
     *     chiffreAffaire: list<string>,
     *     collecte: list<string>,
     *     resteAPayer: list<string>,
     *     depenses: list<string>,
     *     encaissements: list<string>,
     * }
     */
    public function __invoke(): array
    {
        $centreId = $this->context->etablissementId();
        [$start, $end] = $this->window();
        $range = [$start->toDateString(), $end->toDateString()];

        // Chiffre d'affaire — every fee DUE in the month, whatever année its
        // inscription is filed under (see the class docblock: a fee of a
        // 2025/2026 group due in 09/2026 IS September 2026's billing).
        //
        // ⚠ A fee of a CLOSED dossier counts only for what was PAID on it;
        // only an « Active » inscription carries a receivable (class
        // docblock, « Un dossier clos ne doit rien »). One aggregate per fee
        // joined in — never a per-row montantPaye() (perf note below).
        $paye = DB::table('encaissements')
            ->whereNotNull('inscription_fee_id')
            ->groupBy('inscription_fee_id')
            ->selectRaw('inscription_fee_id, SUM(montant) AS total');

        $chiffreAffaire = $this->byMonth(
            DB::table('inscription_fees')
                ->join('inscriptions', 'inscriptions.id', '=', 'inscription_fees.inscription_id')
                ->leftJoinSub($paye, 'paye', 'paye.inscription_fee_id', '=', 'inscription_fees.id')
                ->whereNull('inscription_fees.masque_le')
                ->whereNotNull('inscription_fees.date_echeance')
                ->whereBetween('inscription_fees.date_echeance', $range)
                ->tap(fn (Builder $q) => $this->scopeFeesToContext($q, 'inscription_fees', $centreId)),
            'inscription_fees.date_echeance',
            'CASE WHEN inscriptions.statut = ? THEN inscription_fees.montant ELSE COALESCE(paye.total, 0) END',
            [Inscription::STATUT_ACTIVE],
        );

        // Collecté — payments settling those SAME fees, grouped by the FEE's
        // due month (not the payment month): the exact per-fee montantPaye()
        // semantics, computed in one aggregate instead of one query per fee.
        $collecte = $this->byMonth(
            DB::table('encaissements')
                ->join('inscription_fees', 'inscription_fees.id', '=', 'encaissements.inscription_fee_id')
                ->whereNull('inscription_fees.masque_le')
                ->whereNotNull('inscription_fees.date_echeance')
                ->whereBetween('inscription_fees.date_echeance', $range)
                ->tap(fn (Builder $q) => $this->scopeFeesToContext($q, 'inscription_fees', $centreId)),
            'inscription_fees.date_echeance',
            'encaissements.montant',
        );

        // Dépenses — by date_depense, center via the till.
        // Approuvée ONLY: the yearly recap reports money that actually left
        // the tills, like every caisse screen. A pending, refused or
        // cancelled dépense never left (or came back), so counting it here
        // would make the annual total disagree with the Dépenses list.
        $depenses = $this->byMonth(
            // Centre = the dépense's OWN (VentilationCentre: group, else the
            // centre it was keyed in, else the till's for older rows) — the
            // same rule as the Dépenses list this total must agree with.
            Depense::query()
                ->where('statut', Depense::STATUT_APPROUVEE)
                ->whereBetween('date_depense', $range)
                ->when($centreId, fn ($q) => $this->ventilation->scopeDepensesAuCentre($q, (int) $centreId))
                ->toBase(),
            'date_depense',
        );

        // Encaissements — ALL payments received that month, by date_paiement,
        // centre via the STUDENT: the one definition of "a payment's centre"
        // shared by the Encaissements list, EncaissementPolicy and the
        // dashboard card (GetDashboardStats). Scoping via the till (the
        // previous implementation) attributed the cash of a multi-centre
        // operator to the centre their till lives in, not the centre the
        // money was collected for — the chart and the card disagreed for
        // the same month (audit 24/08/2026).
        // No année filter: money received in a month is that month's money,
        // whichever année's fee it settles (an early payment on next year's
        // inscription was received THIS month) — see the class docblock.
        $encaissements = $this->byMonth(
            DB::table('encaissements')
                ->whereBetween('date_paiement', $range)
                // "Money received" excludes avance APPLICATION rows: applying
                // an avance to a fee writes a second encaissement pointing
                // back at the funding one, so counting both here counted the
                // same dirham twice — once when it entered as an avance, once
                // when it was allocated (31/08/2026, chart showed 504 350
                // where 384 050 was actually received). Same rule as every
                // other money-received total (see application-row convention
                // in GetEncaissementsList).
                ->whereNull('applied_from_encaissement_id')
                ->when($centreId, fn (Builder $q) => $q->whereIn(
                    'student_id',
                    DB::table('students')->select('id')->where('etablissement_id', $centreId),
                )),
            'date_paiement',
        );

        $months = [];
        $caOut = [];
        $collecteOut = [];
        $resteOut = [];
        $depensesOut = [];
        $encaissementsOut = [];

        // One point per month of the window, in calendar order (09/2025 …
        // 08/2026 for a school year).
        $cursor = $start->copy()->startOfMonth();
        $last = $end->copy()->startOfMonth();

        while ($cursor->lessThanOrEqualTo($last)) {
            $key = $cursor->format('Y-m');
            $months[] = $cursor->format('m/Y');

            $ca = round($chiffreAffaire[$key] ?? 0.0, 2);
            $col = round($collecte[$key] ?? 0.0, 2);

            $caOut[] = number_format($ca, 2, '.', '');
            $collecteOut[] = number_format($col, 2, '.', '');
            $resteOut[] = number_format(max(0, $ca - $col), 2, '.', '');
            $depensesOut[] = number_format(round($depenses[$key] ?? 0.0, 2), 2, '.', '');
            $encaissementsOut[] = number_format(round($encaissements[$key] ?? 0.0, 2), 2, '.', '');

            $cursor->addMonth();
        }

        return [
            'months' => $months,
            'chiffreAffaire' => $caOut,
            'collecte' => $collecteOut,
            'resteAPayer' => $resteOut,
            'depenses' => $depensesOut,
            'encaissements' => $encaissementsOut,
        ];
    }

    /**
     * The chart window: the active année scolaire's date range, else the
     * current calendar year.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function window(): array
    {
        $annee = $this->context->anneeScolaire();

        if ($annee !== null) {
            return [Carbon::parse($annee->date_debut), Carbon::parse($annee->date_fin)];
        }

        return [now()->startOfYear(), now()->endOfYear()];
    }

    /**
     * SUM($amountColumn) grouped by the calendar month of $dateColumn, keyed
     * 'YYYY-MM' (months without rows are simply absent).
     *
     * $amountColumn may be a SQL expression; its `?` placeholders are filled
     * from $bindings.
     *
     * @param  list<mixed>  $bindings
     * @return array<string, float>
     */
    private function byMonth(Builder $query, string $dateColumn, string $amountColumn = 'montant', array $bindings = []): array
    {
        $rows = $query
            ->selectRaw("to_char({$dateColumn}, 'YYYY-MM') AS mois, COALESCE(SUM({$amountColumn}), 0) AS total", $bindings)
            ->groupByRaw("to_char({$dateColumn}, 'YYYY-MM')")
            ->get();

        $out = [];

        foreach ($rows as $row) {
            $out[(string) $row->mois] = (float) $row->total;
        }

        return $out;
    }

    /**
     * Fees belong to the active context through their inscription's CENTRE
     * (NULL-centre inscriptions are global). Deliberately NOT through its
     * année: the due month decides the point (class docblock).
     *
     * Cancelled inscriptions are excluded from the billed series entirely
     * (31/08/2026, aligned on the reference WimSchool calculation
     * `REGISTRATION_STATUS_ID <> 10`): a fee of an « Annulée » inscription no
     * longer counts as chiffre d'affaire (nor as collecté — reste à payer is
     * CA − collecté, so the pair must move together). Deliberately
     * `<> Annulée`, NOT `= Active`: « Changement » keeps the earned fees of
     * the pre-change enrollment, and the legacy « Expirée »/« Archivée » rows
     * are completed formations whose revenue is real. The « Encaissements »
     * series is untouched — money received is money received, whatever became
     * of the inscription.
     *
     * ⚠ Narrowing this to `= Active` was proposed and REJECTED (21/09/2026),
     * measured on production over 2026/2027: « Changement » carries 10 146 300
     * DH of fees of which 3 883 250 DH are ALREADY COLLECTED — nearly twice
     * what « Active » has collected (2 051 990 DH). Collecté filters on the
     * same fees, so Active-only would erase that money from BOTH series while
     * it sits in the caisse and on every finance screen: the chart would
     * announce a smaller year than the till actually received. Two screens of
     * the same money that contradict each other (§11). A group change closes
     * the old enrollment and carries its paid fees over — the student studied
     * and paid, the revenue is earned.
     *
     * ⚠ UN DOSSIER CLOS NE DOIT RIEN (21/09/2026). What WAS wrong with
     * « Changement » / « Expirée » / « Archivée » is the UNPAID half: the
     * legacy import gave every closed dossier its full monthly schedule, so
     * the billed series was a FLAT line (Salé 572 000 DH every month from
     * January to August, Rabat 823 000 DH) under a « Reste à payer » of
     * ~2 M DH a month that nobody owes — the student left the group, those
     * months were never due. The reference WimSchool chart shows the opposite
     * for the same data: a past month reads Collecté ≈ Chiffre d'affaire
     * (06/2026: 146 100 / 145 800, reste 300). So a closed dossier's fee
     * counts for what was PAID on it, and only an « Active » inscription
     * carries a receivable — the same rule as « Gestion des recouvrements »
     * (GetRetardsList), which already refuses to chase a closed dossier. No
     * collected dirham leaves either series (the reason Active-only was
     * rejected); only the uncollectible receivable does. The WimSchool server
     * formula is not in its JS bundle — this rule is inferred from its curve
     * and from our own recouvrement rule, not copied from its SQL.
     */
    private function scopeFeesToContext(Builder $query, string $feesTable, ?int $centreId): Builder
    {
        return $query->whereExists(function (Builder $sub) use ($feesTable, $centreId): void {
            $sub->selectRaw('1')
                ->from('inscriptions')
                ->whereColumn('inscriptions.id', "{$feesTable}.inscription_id")
                ->where('inscriptions.statut', '!=', Inscription::STATUT_ANNULEE)
                ->when($centreId, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereNull('inscriptions.etablissement_id')
                    ->orWhere('inscriptions.etablissement_id', $centreId)));
        });
    }
}
