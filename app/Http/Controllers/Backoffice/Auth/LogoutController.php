<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class LogoutController extends Controller
{
    /**
     * Log the current Backoffice user out.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('backoffice.login');
    }
}
