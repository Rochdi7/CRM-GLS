{{-- Colored sexe indicator: blue man icon for Homme, pink woman icon for Femme, — when unknown. --}}
@props(['sexe' => null, 'withLabel' => true])

@if ($sexe === 'Homme')
    <span {{ $attributes->merge(['class' => 'd-inline-flex align-items-center']) }}>
        <i class="ti ti-man fs-16 text-primary" @if (! $withLabel) title="{{ __('Male') }}" @endif></i>
        @if ($withLabel)
            <span class="ms-1">{{ __('Male') }}</span>
        @else
            <span class="visually-hidden">{{ __('Male') }}</span>
        @endif
    </span>
@elseif ($sexe === 'Femme')
    <span {{ $attributes->merge(['class' => 'd-inline-flex align-items-center']) }}>
        <i class="ti ti-woman fs-16 text-pink" @if (! $withLabel) title="{{ __('Female') }}" @endif></i>
        @if ($withLabel)
            <span class="ms-1">{{ __('Female') }}</span>
        @else
            <span class="visually-hidden">{{ __('Female') }}</span>
        @endif
    </span>
@else
    —
@endif
