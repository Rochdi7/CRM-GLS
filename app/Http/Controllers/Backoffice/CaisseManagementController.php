<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Gestion de la caisse — a single tabbed page hosting Ma caisse, the
 * transactions journal, till transfers and the till accounts as Livewire
 * tabs (same pattern as SettingController / DepenseManagementController).
 *
 * Access = ANY of the two view permissions; each tab renders only if its
 * own permission is held (per-tab gating in the view + inside each component).
 */
final class CaisseManagementController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(
            $request->user()->canAny(['cash-registers.view', 'cash-transfers.view']),
            403,
        );

        return view('backoffice.caisses.index');
    }
}
