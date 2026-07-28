{{--
    Backoffice header — adapted from PreSkool `layout/partials/header.blade.php`.
    Structure, classes and element IDs are preserved (script.js and style.css rely
    on them: #toggle_btn, #mobile_btn, #dark-mode-toggle, #light-mode-toggle,
    #btnFullscreen, .header, .header-left, .user-menu, .mobile-user-menu).

    Removed from the original demo: search box, language dropdown, notifications,
    "Add New" mega dropdown, demo chat/report shortcuts. The academic-year +
    center context switchers (Livewire) replace them on the left.
--}}
<div class="header">

    {{-- Logo — GLS branding: black version in light mode (.logo-normal),
         white version in dark mode (.dark-logo, toggled by theme CSS) --}}
    <div class="header-left active">
        <a href="{{ route('backoffice.dashboard') }}" class="logo logo-normal">
            <img src="{{ asset('assets/images/logo/gls-noir.png') }}" alt="{{ config('app.name') }}">
        </a>
        <a href="{{ route('backoffice.dashboard') }}" class="logo-small">
            <img src="{{ asset('assets/images/logo/gls-noir.png') }}" alt="{{ config('app.name') }}">
        </a>
        <a href="{{ route('backoffice.dashboard') }}" class="dark-logo">
            <img src="{{ asset('assets/images/logo/gls-blanc.webp') }}" alt="{{ config('app.name') }}">
        </a>
        <a id="toggle_btn" href="javascript:void(0);">
            <i class="ti ti-menu-deep"></i>
        </a>
    </div>
    {{-- /Logo --}}

    <a id="mobile_btn" class="mobile_btn" href="#sidebar">
        <span class="bar-icon">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </a>

    <div class="header-user">
        <div class="nav user-menu">

            {{-- Spacer pushes the toolbar to the right. --}}
            <div class="nav-item me-auto"></div>

            <div class="d-flex align-items-center">

                {{-- Active-context switchers: academic year + center (Livewire),
                     right-aligned next to the toolbar so the dropdowns open with
                     dropdown-menu-end and clear the fixed sidebar. Selection
                     persists in the session and refreshes context-aware widgets. --}}
                <div class="pe-1">
                    @livewire('backoffice.context.context-switcher')
                </div>

                {{-- Dark / light mode toggles (IDs required by resources/js/backoffice/theme.js) --}}
                <div class="pe-1">
                    <a href="#" id="dark-mode-toggle" class="dark-mode-toggle activate btn btn-outline-light bg-white btn-icon me-1">
                        <i class="ti ti-moon"></i>
                    </a>
                    <a href="#" id="light-mode-toggle" class="dark-mode-toggle btn btn-outline-light bg-white btn-icon me-1">
                        <i class="ti ti-sun"></i>
                    </a>
                </div>

                {{-- Fullscreen toggle (handled by script.js) --}}
                <div class="pe-1">
                    <a href="#" class="btn btn-outline-light bg-white btn-icon me-1" id="btnFullscreen">
                        <i class="ti ti-maximize"></i>
                    </a>
                </div>

                {{-- Profile dropdown --}}
                <div class="dropdown ms-1">
                    <a href="javascript:void(0);" class="dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                        <span class="avatar avatar-md rounded">
                            <img src="{{ asset('assets/preskool/img/profiles/avatar-27.jpg') }}" alt="Img" class="img-fluid">
                        </span>
                    </a>
                    <div class="dropdown-menu">
                        <div class="d-block">
                            <div class="d-flex align-items-center p-2">
                                <span class="avatar avatar-md me-2 online avatar-rounded">
                                    <img src="{{ asset('assets/preskool/img/profiles/avatar-27.jpg') }}" alt="img">
                                </span>
                                <div>
                                    <h6 class="">{{ auth()->user()?->name ?? 'GLS' }}</h6>
                                    <p class="text-primary mb-0">{{ __('Administrator') }}</p>
                                </div>
                            </div>
                            <hr class="m-0">
                            <a class="dropdown-item d-inline-flex align-items-center p-2" href="{{ route('backoffice.profile') }}">
                                <i class="ti ti-user-circle me-2"></i>{{ __('Profile') }}
                            </a>
                            @canany(['centers.view', 'academic-years.view', 'rooms.view'])
                                <a class="dropdown-item d-inline-flex align-items-center p-2" href="{{ route('backoffice.settings') }}">
                                    <i class="ti ti-settings me-2"></i>{{ __('Settings') }}
                                </a>
                            @endcanany
                            <hr class="m-0">
                            <form method="POST" action="{{ route('backoffice.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item d-inline-flex align-items-center p-2">
                                    <i class="ti ti-login me-2"></i>{{ __('Logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Mobile Menu --}}
    <div class="dropdown mobile-user-menu">
        <a href="javascript:void(0);" class="nav-link dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa fa-ellipsis-v"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-end">
            <a class="dropdown-item" href="{{ route('backoffice.profile') }}">{{ __('Profile') }}</a>
            @canany(['centers.view', 'academic-years.view', 'rooms.view'])
                <a class="dropdown-item" href="{{ route('backoffice.settings') }}">{{ __('Settings') }}</a>
            @endcanany
            <form method="POST" action="{{ route('backoffice.logout') }}">
                @csrf
                <button type="submit" class="dropdown-item">{{ __('Logout') }}</button>
            </form>
        </div>
    </div>
    {{-- /Mobile Menu --}}

</div>
