<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Caisse;
use App\Services\Authorization\CenterAccessService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

/**
 * The submitted caisse id must belong to a center the acting user can
 * access (Phase 12 security hardening). Every money form's till options
 * are already center-scoped server-side (e.g.
 * GetCaisseTransfersList::caisseOptions) — this closes the gap where a
 * tampered request could move money through another center's till, which
 * `exists:caisses,id` alone allowed.
 */
final class AccessibleCaisse implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $user = Auth::user();
        $caisse = is_scalar($value) ? Caisse::query()->find($value) : null;

        if ($user === null || $caisse === null) {
            $fail(__('The selected till is invalid.'));

            return;
        }

        $centerId = $caisse->etablissement_id === null ? null : (int) $caisse->etablissement_id;

        if (! app(CenterAccessService::class)->canAccessCenter($user, $centerId)) {
            $fail(__('The selected till is not accessible from your center.'));
        }
    }
}
