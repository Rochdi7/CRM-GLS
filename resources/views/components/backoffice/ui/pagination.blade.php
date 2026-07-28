{{--
    Pagination wrapper — standard placement for paginator links under tables.

    Usage (with a Laravel/Livewire paginator):
        <x-backoffice.ui.pagination :paginator="$students" />
--}}
@props(['paginator'])

@if ($paginator->hasPages())
    <div {{ $attributes->merge(['class' => 'd-flex align-items-center justify-content-between flex-wrap p-3']) }}>
        <p class="text-muted mb-0">
            {{ __('Showing :from to :to of :total results', [
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ]) }}
        </p>
        {{ $paginator->links() }}
    </div>
@endif
