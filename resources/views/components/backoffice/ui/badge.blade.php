{{--
    Badge — PreSkool soft badge.

    Usage:
        <x-backoffice.ui.badge variant="success">{{ __('Active') }}</x-backoffice.ui.badge>
        <x-backoffice.ui.badge variant="danger" :soft="false">…</x-backoffice.ui.badge>
--}}
@props(['variant' => 'primary', 'soft' => true])

<span {{ $attributes->merge(['class' => $soft ? "badge badge-soft-{$variant}" : "badge bg-{$variant}"]) }}>{{ $slot }}</span>
