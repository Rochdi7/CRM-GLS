<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice\Concerns;

use App\Models\Group;
use App\Services\Authorization\CenterAccessService;
use App\Services\Context\CurrentContext;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Guard for store() paths whose scope (centre + année) is inherited from a
 * CLIENT-CHOSEN parent group (séances, créneaux, dépenses «Paiement prof»).
 * The inheritance mechanism is correct — but only if the parent itself sits
 * inside the user's reach AND the active working context; otherwise a forged
 * or stale group_id writes records into a foreign centre/year where no list
 * shows them (the importer bug of 24/08/2026, same class).
 *
 * ResourcePolicy::create() cannot cover this: it takes no model, so no
 * centre check runs on creation — every such store() must call this.
 */
trait AssertsContextScope
{
    private function assertGroupInContext(Request $request, Group $group): void
    {
        abort_unless(
            app(CenterAccessService::class)->canAccessCenter($request->user(), $group->etablissement_id),
            403,
        );

        $context = app(CurrentContext::class);

        $contextCentre = $context->etablissementId();
        if ($contextCentre !== null && (int) $group->etablissement_id !== $contextCentre) {
            throw ValidationException::withMessages([
                'group_id' => __('This group belongs to another centre than the active one.'),
            ]);
        }

        $anneeId = $context->anneeScolaireId();
        if ($anneeId !== null && $group->annee_scolaire_id !== null && (int) $group->annee_scolaire_id !== $anneeId) {
            throw ValidationException::withMessages([
                'group_id' => __('This group belongs to another academic year than the active one.'),
            ]);
        }
    }
}
