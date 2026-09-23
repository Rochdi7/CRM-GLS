<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Reports\Actions\GetAnnualFraisSummary;
use App\Domain\Reports\Actions\GetDashboardStats;
use App\Domain\Reports\Actions\GetNouvellesInscriptionsChart;
use App\Domain\Reports\Actions\GetSeancesCalendar;
use App\Http\Controllers\Controller;
use App\Services\Context\CurrentContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetDashboardStats $getDashboardStats,
        GetAnnualFraisSummary $getAnnualFraisSummary,
        GetSeancesCalendar $getSeancesCalendar,
        GetNouvellesInscriptionsChart $getNouvellesInscriptions,
        CurrentContext $context,
    ): Response {
        // "Résumé des séances" calendar month — the chart itself follows the
        // top-bar année scolaire switcher (CurrentContext), no per-widget
        // year parameter any more.
        $calMonth = (string) $request->string('calMonth');

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $calMonth) !== 1) {
            $calMonth = now()->format('Y-m');
        }

        // « Nouvelles inscriptions » bar chart — super-admin only. The prop is
        // NULL for everyone else (the data never leaves the server), not just
        // hidden by the component.
        $canSeeInscriptionsChart = (bool) $request->user()?->can('dashboard.inscriptions-chart');
        $duree = (string) $request->string('inscDuree', GetNouvellesInscriptionsChart::DUREE_DEFAUT);

        // Every widget is a closure so Inertia partial reloads
        // (`only: ['seancesCalendar']` when paging the calendar) compute
        // ONLY the requested widget. A plain value here would still be
        // evaluated server-side and then dropped from the response.
        return Inertia::render('Backoffice/Dashboard/Index', [
            'stats' => fn () => $getDashboardStats($context)->toArray(),
            'annualFrais' => fn () => $getAnnualFraisSummary(),
            'annualFraisPeriode' => fn () => $getAnnualFraisSummary->periodeLabel(),
            'seancesCalendar' => fn () => $getSeancesCalendar($context, $calMonth),
            'nouvellesInscriptions' => fn () => $canSeeInscriptionsChart ? $getNouvellesInscriptions($duree) : null,
        ]);
    }
}
