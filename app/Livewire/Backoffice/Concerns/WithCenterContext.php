<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Concerns;

use App\Services\Context\CurrentContext;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

/**
 * Makes a Livewire list follow the active working context (top-bar center
 * switcher): the component re-renders when the context changes and its
 * queries are narrowed to the SELECTED center.
 *
 * This is separate from permission scoping (CenterAccessService — "which
 * centers MAY this user see"): the context filter narrows further to the one
 * center currently selected. NULL-center records stay visible everywhere
 * (they are global by invariant — see CenterAccessService).
 */
trait WithCenterContext
{
    /** Refresh when the top-bar context switcher changes the year/center. */
    #[On('context-changed')]
    public function onContextChanged(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /**
     * Constrain a query to the selected center (+ global NULL-center rows).
     * No-op when the context is "Tous les centres".
     */
    protected function scopeToActiveCenter(Builder $query, string $column = 'etablissement_id'): Builder
    {
        $id = app(CurrentContext::class)->etablissementId();

        if ($id === null) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, $id));
    }
}
