<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('backoffice.dashboard.index');
    }
}
