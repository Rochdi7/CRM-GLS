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
 * silently releasing it would move cash nobody approved. « Validation des
 * remboursements » (02/11/2026) follows the same rule for pending refund
 * requests.
 *
 * Each switch is submitted on its own (the panel sends one key per toggle),
 * so every key is `sometimes` — and each change is journaled separately.
 */
final class SystemSettingController extends Controller
{
    /** Request key => [AppSettings key, French label for the journal]. */
    private const SWITCHES = [
        'expense_approval' => [AppSettings::EXPENSE_APPROVAL, 'Validation des dépenses'],
        'refund_approval' => [AppSettings::REFUND_APPROVAL, 'Validation des remboursements'],
    ];

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('system-settings.update'), 403);

        $validated = $request->validate([
            'expense_approval' => ['sometimes', 'required', 'boolean'],
            'refund_approval' => ['sometimes', 'required', 'boolean'],
        ]);

        foreach (self::SWITCHES as $field => [$key, $label]) {
            if (! array_key_exists($field, $validated)) {
                continue;
            }

            $on = (bool) $validated[$field];
            AppSettings::setBool($key, $on);

            // Explicitly journaled: a switch that changes whether money can
            // leave a till without approval must be traceable to a person
            // and a moment.
            activity('system-settings')
                ->causedBy($request->user())
                ->withProperties([
                    'reglage' => $label,
                    'valeur' => $on ? 'Activée' : 'Désactivée',
                ])
                ->log($label.($on ? ' activée' : ' désactivée'));
        }

        return back()->with('success', __('System settings updated.'));
    }
}
