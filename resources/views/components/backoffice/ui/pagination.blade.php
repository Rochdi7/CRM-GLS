{{--
    Pagination wrapper — standard placement for paginator links under tables.

    Usage (with a Laravel/Livewire paginator): x-backoffice.ui.pagination :paginator="$students" self-closing.

    To add a "rows per page" control in the same row, pass
    x-backoffice.ui.per-page-select as the default slot instead of
    self-closing the pagination tag.
--}}
@props(['paginator'])

@if ($paginator->hasPages() || (isset($slot) && $slot->isNotEmpty()))
    <div {{ $attributes->merge(['class' => 'd-flex align-items-center justify-content-between flex-wrap gap-2 p-3']) }}>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            @isset($slot)
                {{ $slot }}
            @endisset
            <p class="text-muted mb-0">
                {{ __('Showing :from to :to of :total results', [
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ]) }}
            </p>
        </div>
        {{ $paginator->links('vendor.pagination.backoffice-links') }}
    </div>
@endif
