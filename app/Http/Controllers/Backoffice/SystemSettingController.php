<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Support\Settings\AppSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Paramètres → Système — application-wide switches.
 *
 * Only `system-settings.update` (no role preset holds it, so effectively
 * super-admins) may flip a switch. Turning « Validation des dépenses » OFF
 * does NOT retroactively approve pending expenses: those stay "En attente"
 * and still need a decision, because their money was never debited and
 * silently releasing it would move cash nobody approved.
 */
final class SystemSettingController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('system-settings.update'), 403);

        $validated = $request->validate([
            'expense_approval' => ['required', 'boolean'],
        ]);

        AppSettings::setBool(AppSettings::EXPENSE_APPROVAL, (bool) $validated['expense_approval']);

        // Explicitly journaled: a switch that changes whether money can leave
        // a till without approval must be traceable to a person and a moment.
        activity('system-settings')
            ->causedBy($request->user())
            ->withProperties([
                'reglage' => 'Validation des dépenses',
                'valeur' => $validated['expense_approval'] ? 'Activée' : 'Désactivée',
            ])
            ->log($validated['expense_approval']
                ? 'Validation des dépenses activée'
                : 'Validation des dépenses désactivée');

        return back()->with('success', __('System settings updated.'));
    }
}
