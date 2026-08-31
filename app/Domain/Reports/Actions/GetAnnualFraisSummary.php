<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

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
 *  - chiffreAffaire: sum of InscriptionFee.montant whose date_echeance falls
 *    in that month (what was billed/due that month);
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
        $anneeId = $this->context->anneeScolaireId();
        [$start, $end] = $this->window();
        $range = [$start->toDateString(), $end->toDateString()];

        // Chiffre d'affaire — fees of the ACTIVE ANNÉE's inscriptions
        // (annee_scolaire_id chain, CLAUDE.md §11 context scoping — the date
        // window alone let another année's fees leak in whenever their due
        // dates fell inside this année's months, e.g. 2025/2026 monthly fees
        // due Sep–Dec 2026 showing under 2026/2027), grouped by due month.
        $chiffreAffaire = $this->byMonth(
            DB::table('inscription_fees')
                ->whereNull('masque_le')
                ->whereNotNull('date_echeance')
                ->whereBetween('date_echeance', $range)
                ->tap(fn (Builder $q) => $this->scopeFeesToContext($q, 'inscription_fees', $centreId, $anneeId)),
            'date_echeance',
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
                ->tap(fn (Builder $q) => $this->scopeFeesToContext($q, 'inscription_fees', $centreId, $anneeId)),
            'inscription_fees.date_echeance',
            'encaissements.montant',
        );

        // Dépenses — by date_depense, center via the till.
        $depenses = $this->byMonth(
            DB::table('depenses')
                ->whereBetween('date_depense', $range)
                ->when($centreId, fn (Builder $q) => $q->whereIn(
                    'caisse_id',
                    DB::table('caisses')->select('id')->where('etablissement_id', $centreId),
                )),
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
        // Année scoping (§11): fee-linked payments belong to their fee's
        // inscription année; avances (no fee) are matched by their payment
        // date falling in the window — same split as the Encaissements list.
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
                ))
                ->when($anneeId, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereNull('encaissements.inscription_fee_id')
                    ->orWhereExists(function (Builder $sub) use ($anneeId): void {
                        $sub->selectRaw('1')
                            ->from('inscription_fees')
                            ->join('inscriptions', 'inscriptions.id', '=', 'inscription_fees.inscription_id')
                            ->whereColumn('inscription_fees.id', 'encaissements.inscription_fee_id')
                            ->where('inscriptions.annee_scolaire_id', $anneeId);
                    }))),
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
     * @return array<string, float>
     */
    private function byMonth(Builder $query, string $dateColumn, string $amountColumn = 'montant'): array
    {
        $rows = $query
            ->selectRaw("to_char({$dateColumn}, 'YYYY-MM') AS mois, COALESCE(SUM({$amountColumn}), 0) AS total")
            ->groupByRaw("to_char({$dateColumn}, 'YYYY-MM')")
            ->get();

        $out = [];

        foreach ($rows as $row) {
            $out[(string) $row->mois] = (float) $row->total;
        }

        return $out;
    }

    /**
     * Fees belong to the active context through their inscription: the active
     * centre (NULL-centre inscriptions are global — same rule as before) AND
     * the active année scolaire (hard filter — an inscription always carries
     * its année).
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
     */
    private function scopeFeesToContext(Builder $query, string $feesTable, ?int $centreId, ?int $anneeId): Builder
    {
        return $query->whereExists(function (Builder $sub) use ($feesTable, $centreId, $anneeId): void {
            $sub->selectRaw('1')
                ->from('inscriptions')
                ->whereColumn('inscriptions.id', "{$feesTable}.inscription_id")
                ->where('inscriptions.statut', '!=', Inscription::STATUT_ANNULEE)
                ->when($centreId, fn (Builder $q) => $q->where(fn (Builder $w) => $w
                    ->whereNull('inscriptions.etablissement_id')
                    ->orWhere('inscriptions.etablissement_id', $centreId)))
                ->when($anneeId, fn (Builder $q) => $q->where('inscriptions.annee_scolaire_id', $anneeId));
        });
    }
}
