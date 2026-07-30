<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Gestion des dépenses — a single tabbed page hosting the two remaining
 * expense modules (dépenses, remboursements) as Livewire tabs, mirroring the
 * Paramètres page pattern (SettingController). Types de dépenses moved OUT
 * to its own Inertia page in Phase 6 (docs/phase-6-simple-crud-inventory.md
 * §Q2) — this page is 2 tabs only now.
 *
 * Access = ANY of the two view permissions; each tab renders only if its own
 * permission is held (per-tab gating in the view + inside each component).
 */
final class DepenseManagementController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(
            $request->user()->canAny(['expenses.view', 'refunds.view']),
            403,
        );

        return view('backoffice.depenses.index');
    }
}
