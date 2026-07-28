{{-- Active-context switchers (academic year + center) for the top bar. --}}
<div class="gls-context-switcher d-flex align-items-center flex-wrap gap-2">

    {{-- Academic year --}}
    <div class="dropdown">
        <a href="javascript:void(0);"
            class="btn btn-outline-light bg-white d-flex align-items-center dropdown-toggle"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="ti ti-calendar me-2"></i>
            <span class="fw-semibold">{{ $anneeActive?->nom ?? __('Academic Year') }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            @foreach ($annees as $annee)
                <a class="dropdown-item d-flex align-items-center justify-content-between {{ $anneeActive?->id === $annee->id ? 'active' : '' }}"
                    href="javascript:void(0);" wire:click="selectAnnee({{ $annee->id }})" wire:key="annee-{{ $annee->id }}">
                    <span>{{ $annee->nom }}</span>
                    @if ($annee->par_defaut)
                        <span class="badge badge-soft-info ms-2">{{ __('Default') }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>

    {{-- Center --}}
    <div class="dropdown">
        <a href="javascript:void(0);"
            class="btn btn-outline-light bg-white d-flex align-items-center {{ $canSwitchCenter ? 'dropdown-toggle' : '' }}"
            @if ($canSwitchCenter) data-bs-toggle="dropdown" aria-expanded="false" @endif>
            <i class="ti ti-building me-2"></i>
            <span class="fw-semibold">
                {{ $isAllCenters ? __('All centers') : ($centreActif?->nom_centre ?? __('Center')) }}
            </span>
        </a>
        @if ($canSwitchCenter)
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item {{ $isAllCenters ? 'active' : '' }}"
                    href="javascript:void(0);" wire:click="selectCentre(null)">
                    <i class="ti ti-world me-2"></i>{{ __('All centers') }}
                </a>
                <div class="dropdown-divider"></div>
                @foreach ($centres as $centre)
                    <a class="dropdown-item {{ ! $isAllCenters && $centreActif?->id === $centre->id ? 'active' : '' }}"
                        href="javascript:void(0);" wire:click="selectCentre({{ $centre->id }})" wire:key="centre-{{ $centre->id }}">
                        {{ $centre->nom_centre }}
                        @if ($centre->siege_social)
                            <span class="badge badge-soft-warning ms-2">{{ __('Head office') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

</div>
