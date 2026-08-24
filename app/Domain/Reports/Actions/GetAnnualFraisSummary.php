<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Services\Context\CurrentContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * "Résumé des frais annuels" dashboard chart — 12 monthly points for the
 * given year, 5 series (docs clarified 2026-08-14):
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
 * GetDashboardStats); NOT scoped by academic year — the chart is a fixed
 * calendar-year view with its own year selector, independent of the top-bar
 * année scolaire switcher.
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
    public function __invoke(int $year): array
    {
        $centreId = $this->context->etablissementId();
        $range = ["{$year}-01-01", "{$year}-12-31"];

        // Chiffre d'affaire — fees due in the year, grouped by due month.
        $chiffreAffaire = $this->byMonth(
            DB::table('inscription_fees')
                ->whereNotNull('date_echeance')
                ->whereBetween('date_echeance', $range)
                ->when($centreId, fn (Builder $q) => $this->scopeFeesToCenter($q, 'inscription_fees', $centreId)),
            'date_echeance',
        );

        // Collecté — payments settling those SAME fees, grouped by the FEE's
        // due month (not the payment month): the exact per-fee montantPaye()
        // semantics, computed in one aggregate instead of one query per fee.
        $collecte = $this->byMonth(
            DB::table('encaissements')
                ->join('inscription_fees', 'inscription_fees.id', '=', 'encaissements.inscription_fee_id')
                ->whereNotNull('inscription_fees.date_echeance')
                ->whereBetween('inscription_fees.date_echeance', $range)
                ->when($centreId, fn (Builder $q) => $this->scopeFeesToCenter($q, 'inscription_fees', $centreId)),
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
        // center via the till (same scoping as the previous implementation).
        $encaissements = $this->byMonth(
            DB::table('encaissements')
                ->whereBetween('date_paiement', $range)
                ->when($centreId, fn (Builder $q) => $q->whereIn(
                    'caisse_id',
                    DB::table('caisses')->select('id')->where('etablissement_id', $centreId),
                )),
            'date_paiement',
        );

        $months = [];
        $caOut = [];
        $collecteOut = [];
        $resteOut = [];
        $depensesOut = [];
        $encaissementsOut = [];

        for ($m = 1; $m <= 12; $m++) {
            $months[] = sprintf('%02d/%d', $m, $year);
            $ca = round($chiffreAffaire[$m], 2);
            $col = round($collecte[$m], 2);

            $caOut[] = number_format($ca, 2, '.', '');
            $collecteOut[] = number_format($col, 2, '.', '');
            $resteOut[] = number_format(max(0, $ca - $col), 2, '.', '');
            $depensesOut[] = number_format(round($depenses[$m], 2), 2, '.', '');
            $encaissementsOut[] = number_format(round($encaissements[$m], 2), 2, '.', '');
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
     * Years offered in the selector — every year with at least one due fee,
     * always including the current year. One DISTINCT aggregate, never a
     * full-table hydration.
     *
     * @return list<int>
     */
    public function availableYears(): array
    {
        $centreId = $this->context->etablissementId();

        $fromFees = DB::table('inscription_fees')
            ->whereNotNull('date_echeance')
            ->when($centreId, fn (Builder $q) => $this->scopeFeesToCenter($q, 'inscription_fees', $centreId))
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM date_echeance)::int AS annee')
            ->pluck('annee')
            ->map(fn ($y) => (int) $y);

        return $fromFees->push((int) now()->year)->unique()->sortDesc()->values()->all();
    }

    /**
     * SUM($amountColumn) grouped by the calendar month of $dateColumn, as a
     * 1..12 array (months without rows read 0.0).
     *
     * @return array<int, float>
     */
    private function byMonth(Builder $query, string $dateColumn, string $amountColumn = 'montant'): array
    {
        $out = array_fill(1, 12, 0.0);

        $rows = $query
            ->selectRaw("EXTRACT(MONTH FROM {$dateColumn})::int AS mois, COALESCE(SUM({$amountColumn}), 0) AS total")
            ->groupByRaw("EXTRACT(MONTH FROM {$dateColumn})")
            ->get();

        foreach ($rows as $row) {
            $month = (int) $row->mois;

            if ($month >= 1 && $month <= 12) {
                $out[$month] = (float) $row->total;
            }
        }

        return $out;
    }

    /**
     * Fees belong to the active centre through their inscription (NULL-centre
     * inscriptions are global — same rule as before).
     */
    private function scopeFeesToCenter(Builder $query, string $feesTable, int $centreId): Builder
    {
        return $query->whereExists(function (Builder $sub) use ($feesTable, $centreId): void {
            $sub->selectRaw('1')
                ->from('inscriptions')
                ->whereColumn('inscriptions.id', "{$feesTable}.inscription_id")
                ->where(fn (Builder $w) => $w
                    ->whereNull('inscriptions.etablissement_id')
                    ->orWhere('inscriptions.etablissement_id', $centreId));
        });
    }
}
