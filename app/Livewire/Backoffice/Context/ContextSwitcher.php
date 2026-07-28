<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Context;

use App\Services\Context\CurrentContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Top-bar switchers for the active academic year and center.
 * Persists the choice in the session (via CurrentContext) and dispatches
 * `context-changed`, which context-aware components (e.g. the dashboard
 * stats) listen for to refresh — no full page reload needed.
 */
final class ContextSwitcher extends Component
{
    public function selectAnnee(int $id): void
    {
        app(CurrentContext::class)->setAnneeScolaire($id);
        $this->dispatch('context-changed');
    }

    public function selectCentre(?int $id): void
    {
        app(CurrentContext::class)->setEtablissement($id);
        $this->dispatch('context-changed');
    }

    public function render(): View
    {
        $context = app(CurrentContext::class);

        return view('livewire.backoffice.context.context-switcher', [
            'anneeActive' => $context->anneeScolaire(),
            'annees' => $context->availableAnnees(),
            'centreActif' => $context->etablissement(),
            'centres' => $context->availableCentres(),
            'isAllCenters' => $context->isAllCenters(),
            'canSwitchCenter' => $context->canSwitchCenter(),
        ]);
    }
}
