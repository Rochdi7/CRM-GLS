<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('frontoffice.home.index');
    }
}
