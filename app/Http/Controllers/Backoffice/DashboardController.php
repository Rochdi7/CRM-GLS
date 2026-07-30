<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Domain\Reports\Actions\GetDashboardStats;
use App\Http\Controllers\Controller;
use App\Services\Context\CurrentContext;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(GetDashboardStats $getDashboardStats, CurrentContext $context): Response
    {
        return Inertia::render('Backoffice/Dashboard/Index', [
            'stats' => $getDashboardStats($context)->toArray(),
        ]);
    }
}
