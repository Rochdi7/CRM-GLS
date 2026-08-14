<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Reports\Actions\GetAnnualFraisSummary;
use App\Domain\Reports\Actions\GetDashboardStats;
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
        CurrentContext $context,
    ): Response {
        $year = (int) $request->integer('year', (int) now()->year);

        return Inertia::render('Backoffice/Dashboard/Index', [
            'stats' => $getDashboardStats($context)->toArray(),
            'annualFrais' => $getAnnualFraisSummary($year),
            'annualFraisYear' => $year,
            'annualFraisYears' => $getAnnualFraisSummary->availableYears(),
        ]);
    }
}
