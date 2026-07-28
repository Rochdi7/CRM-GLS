<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Gestion des dépenses — a single tabbed page hosting the three expense
 * modules (dépenses, remboursements, types de dépenses) as Livewire tabs,
 * mirroring the Paramètres page pattern (SettingController).
 *
 * Access = ANY of the three view permissions; each tab renders only if its
 * own permission is held (per-tab gating in the view + inside each component).
 */
final class DepenseManagementController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(
            $request->user()->canAny(['expenses.view', 'refunds.view', 'expense-types.view']),
            403,
        );

        return view('backoffice.depenses.index');
    }
}
