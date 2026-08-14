<?php

declare(strict_types=1);

namespace App\Domain\Reports\Actions;

use App\Models\Depense;
use App\Models\Encaissement;
use App\Models\InscriptionFee;
use App\Services\Context\CurrentContext;
use Illuminate\Support\Carbon;

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

        $chiffreAffaire = array_fill(1, 12, 0.0);
        $collecte = array_fill(1, 12, 0.0);
        $depenses = array_fill(1, 12, 0.0);
        $encaissements = array_fill(1, 12, 0.0);

        // Chiffre d'affaire + Collecté — one pass over the year's due fees,
        // each fee's own montantPaye() (never a cached column) same as
        // GetRetardsList/GetInscriptionUnpaidFees.
        InscriptionFee::query()
            ->with('encaissements')
            ->whereNotNull('date_echeance')
            ->whereBetween('date_echeance', ["{$year}-01-01", "{$year}-12-31"])
            ->when($centreId, fn ($q) => $q->whereHas(
                'inscription',
                fn ($q) => $q->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $centreId)),
            ))
            ->get()
            ->each(function (InscriptionFee $fee) use (&$chiffreAffaire, &$collecte): void {
                $month = (int) Carbon::parse($fee->date_echeance)->month;
                $chiffreAffaire[$month] += (float) $fee->montant;
                $collecte[$month] += $fee->montantPaye();
            });

        // Dépenses — by date_depense, center via the till.
        Depense::query()
            ->whereBetween('date_depense', ["{$year}-01-01", "{$year}-12-31"])
            ->when($centreId, fn ($q) => $q->whereHas('caisse', fn ($c) => $c->where('etablissement_id', $centreId)))
            ->selectRaw('date_depense, montant')
            ->get()
            ->each(function ($row) use (&$depenses): void {
                $month = (int) Carbon::parse($row->date_depense)->month;
                $depenses[$month] += (float) $row->montant;
            });

        // Encaissements — ALL payments received that month, by date_paiement,
        // center via the till (same scoping as GetDashboardStats::paymentsMonth).
        Encaissement::query()
            ->whereBetween('date_paiement', ["{$year}-01-01", "{$year}-12-31"])
            ->when($centreId, fn ($q) => $q->whereHas('caisse', fn ($c) => $c->where('etablissement_id', $centreId)))
            ->selectRaw('date_paiement, montant')
            ->get()
            ->each(function ($row) use (&$encaissements): void {
                $month = (int) Carbon::parse($row->date_paiement)->month;
                $encaissements[$month] += (float) $row->montant;
            });

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

    /** Years offered in the selector — every year with at least one due fee or expense, always including the current year. */
    public function availableYears(): array
    {
        $centreId = $this->context->etablissementId();

        // Cast to a plain Support\Collection via ->all() first — an Eloquent
        // Collection's unique()/getDictionary() assume model items and break
        // once ->map() has replaced them with plain ints.
        $fromFees = collect(
            InscriptionFee::query()
                ->whereNotNull('date_echeance')
                ->when($centreId, fn ($q) => $q->whereHas(
                    'inscription',
                    fn ($q) => $q->where(fn ($q) => $q->whereNull('etablissement_id')->orWhere('etablissement_id', $centreId)),
                ))
                ->selectRaw('date_echeance')
                ->get()
                ->all(),
        )->map(fn ($row) => (int) Carbon::parse($row->date_echeance)->year);

        $years = $fromFees->push((int) now()->year)->unique()->sortDesc()->values()->all();

        return $years;
    }
}
