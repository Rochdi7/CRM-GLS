<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Paramètres — a single tabbed page hosting the referential-data CRUD
 * (établissements, années scolaires, salles) as Livewire components.
 *
 * Access = ANY of the three view permissions; each tab renders only if its
 * own permission is held (per-tab gating in the view + inside each component).
 * No dedicated `settings.*` permission — reuses the seeded resource
 * permissions the policies already enforce.
 */
final class SettingController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless(
            $request->user()->canAny(['centers.view', 'academic-years.view', 'rooms.view', 'fees.view']),
            403,
        );

        return view('backoffice.settings.index');
    }
}
