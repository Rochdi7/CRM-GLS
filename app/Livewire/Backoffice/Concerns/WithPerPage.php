<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Concerns;

/**
 * Adds a Livewire-driven "rows per page" control to a paginated list —
 * server-side pagination stays in charge (CLAUDE.md § DataTables rule),
 * this only lets the user pick the page size instead of it being hard-coded.
 *
 * Usage: `use WithPagination, WithPerPage;` on the component, call
 * `->paginate($this->perPage)` in render(), and render
 * `<x-backoffice.ui.per-page-select />` next to the table.
 */
trait WithPerPage
{
    /** @var list<int> */
    protected array $perPageOptions = [10, 25, 50, 100];

    public int $perPage = 10;

    public function updatingPerPage(mixed $value): void
    {
        if (! in_array((int) $value, $this->perPageOptions, true)) {
            $this->perPage = 10;

            return;
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
