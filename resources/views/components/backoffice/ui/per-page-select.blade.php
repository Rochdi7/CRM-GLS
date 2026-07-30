{{--
    "Rows per page" selector for a Livewire-paginated list — server-side
    pagination stays in charge (CLAUDE.md § DataTables rule); this only
    changes the page size instead of it being fixed at 10.

    Requires the component to `use WithPerPage` (App\Livewire\Backoffice\Concerns\WithPerPage).

    Usage:
        <x-backoffice.ui.per-page-select />
--}}
@props(['options' => [10, 25, 50, 100]])

<div class="d-flex align-items-center gap-2">
    <span class="text-muted">{{ __('Rows per page') }}</span>
    <select wire:model.live="perPage" class="form-select form-select-sm" style="width: auto;">
        @foreach ($options as $option)
            <option value="{{ $option }}">{{ $option }}</option>
        @endforeach
    </select>
</div>
