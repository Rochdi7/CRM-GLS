<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Requests\Backoffice\Context\UpdateContextRequest;
use App\Services\Context\CurrentContext;
use Illuminate\Http\RedirectResponse;

/**
 * Persists the top-bar academic-year/center selection. CurrentContext (the
 * single source of truth, shared with every remaining Livewire page too —
 * see docs/inertia-react-migration-status.md Phase 4 "mixed stack"
 * section) owns all real authorization: an inaccessible or invalid id is
 * silently ignored, never trusted from the request as-is.
 */
final class ContextController
{
    public function update(UpdateContextRequest $request, CurrentContext $context): RedirectResponse
    {
        if ($request->filled('annee_scolaire_id')) {
            $context->setAnneeScolaire((int) $request->integer('annee_scolaire_id'));
        }

        if ($request->has('etablissement_id')) {
            $etablissementId = $request->input('etablissement_id');
            $context->setEtablissement($etablissementId === null ? null : (int) $etablissementId);
        }

        return back()->with('success', __('Context updated.'));
    }
}
