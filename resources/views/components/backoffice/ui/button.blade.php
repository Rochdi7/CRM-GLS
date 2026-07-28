{{--
    Button — Bootstrap button preserving PreSkool styling.

    Usage:
        <x-backoffice.ui.button>{{ __('Save') }}</x-backoffice.ui.button>
        <x-backoffice.ui.button variant="light" type="button" icon="ti ti-x">{{ __('Cancel') }}</x-backoffice.ui.button>
        <x-backoffice.ui.button href="{{ route('…') }}" variant="primary" icon="ti ti-square-rounded-plus">{{ __('Add') }}</x-backoffice.ui.button>
        <x-backoffice.ui.button type="button" icon="ti ti-square-rounded-plus" wire:click="create" loading="create">{{ __('Add') }}</x-backoffice.ui.button>

    `loading` (Livewire only) — action name(s) for wire:target: while that action runs,
    the button is disabled and the icon is replaced by a spinner.
--}}
@props(['variant' => 'primary', 'type' => 'submit', 'href' => null, 'icon' => null, 'loading' => null])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "btn btn-{$variant} d-inline-flex align-items-center"]) }}>
        @if ($icon)<i class="{{ $icon }} me-2"></i>@endif{{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "btn btn-{$variant} d-inline-flex align-items-center"]) }}
        @if ($loading) wire:loading.attr="disabled" wire:target="{{ $loading }}" @endif>
        @if ($loading)
            <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="{{ $loading }}" role="status" aria-hidden="true"></span>
        @endif
        @if ($icon)<i class="{{ $icon }} me-2" @if ($loading) wire:loading.remove wire:target="{{ $loading }}" @endif></i>@endif{{ $slot }}
    </button>
@endif
