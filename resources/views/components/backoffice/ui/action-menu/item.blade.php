{{--
    One entry of <x-backoffice.ui.action-menu>. Renders a link when `href` is
    given, otherwise a button — pass wire:click / wire:confirm through
    attributes.

    Props:
        icon    Tabler icon class without the "ti " prefix (e.g. "ti-edit")
        href    link target (defaults to a button)
        danger  red text for destructive actions
--}}
@props(['icon' => null, 'href' => null, 'danger' => false])

<li>
    @if ($href)
        <a href="{{ $href }}" {{ $attributes->merge(['class' => 'dropdown-item rounded-1'.($danger ? ' text-danger' : '')]) }}>
            @if ($icon)<i class="ti {{ $icon }} me-2"></i>@endif{{ $slot }}
        </a>
    @else
        <button type="button" {{ $attributes->merge(['class' => 'dropdown-item rounded-1'.($danger ? ' text-danger' : '')]) }}>
            @if ($icon)<i class="ti {{ $icon }} me-2"></i>@endif{{ $slot }}
        </button>
    @endif
</li>
