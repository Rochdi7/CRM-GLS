<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Reports\Actions\GetAnnualFraisSummary;
use App\Domain\Reports\Actions\GetDashboardStats;
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
        CurrentContext $context,
    ): Response {
        $year = (int) $request->integer('year', (int) now()->year);

        // "Résumé des séances" calendar month — independent of the chart's
        // year filter so the two widgets navigate without disturbing each other.
        $calMonth = (string) $request->string('calMonth');

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $calMonth) !== 1) {
            $calMonth = now()->format('Y-m');
        }

        // Every widget is a closure so Inertia partial reloads
        // (`only: ['seancesCalendar']` when paging the calendar,
        // `only: ['annualFrais', …]` when changing the chart year) compute
        // ONLY the requested widget. A plain value here would still be
        // evaluated server-side and then dropped from the response.
        return Inertia::render('Backoffice/Dashboard/Index', [
            'stats' => fn () => $getDashboardStats($context)->toArray(),
            'annualFrais' => fn () => $getAnnualFraisSummary($year),
            'annualFraisYear' => $year,
            'annualFraisYears' => fn () => $getAnnualFraisSummary->availableYears(),
            'seancesCalendar' => fn () => $getSeancesCalendar($context, $calMonth),
        ]);
    }
}
