{{--
    Page header (title + breadcrumbs + optional action buttons) — markup taken
    from the PreSkool page headers (see theme-reference dashboards/students).

    Usage:
        <x-backoffice.layout.page-header
            :title="__('Dashboard')"
            :breadcrumbs="[__('Dashboard') => null]">
            <x-slot:actions>
                <a href="#" class="btn btn-primary d-flex align-items-center">…</a>
            </x-slot:actions>
        </x-backoffice.layout.page-header>
--}}
@props(['title', 'breadcrumbs' => []])

<div class="d-md-flex d-block align-items-center justify-content-between mb-3">
    <div class="my-auto mb-2">
        <h3 class="page-title mb-1">{{ $title }}</h3>
        @if ($breadcrumbs !== [])
            <x-backoffice.layout.breadcrumbs :items="$breadcrumbs" />
        @endif
    </div>
    @isset($actions)
        <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
            {{ $actions }}
        </div>
    @endisset
</div>
