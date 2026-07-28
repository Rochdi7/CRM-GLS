{{--
    Empty state — shown when a list/table has no records.

    Usage:
        <x-backoffice.ui.empty-state :title="__('No students yet')" :message="__('Add your first student to get started.')">
            <x-backoffice.ui.button icon="ti ti-square-rounded-plus">{{ __('Add Student') }}</x-backoffice.ui.button>
        </x-backoffice.ui.empty-state>
--}}
@props(['title', 'message' => null, 'icon' => 'ti ti-database-off'])

<div {{ $attributes->merge(['class' => 'text-center py-5']) }}>
    <span class="avatar avatar-xl bg-light rounded-circle mb-3 d-inline-flex align-items-center justify-content-center">
        <i class="{{ $icon }} fs-24 text-muted"></i>
    </span>
    <h5 class="mb-1">{{ $title }}</h5>
    @if ($message)
        <p class="text-muted mb-3">{{ $message }}</p>
    @endif
    {{ $slot }}
</div>
