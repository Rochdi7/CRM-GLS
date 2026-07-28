{{-- Frontoffice site header — simple Bootstrap navbar, independent from the admin shell. --}}
<header class="bg-white border-bottom">
    <nav class="navbar navbar-expand-lg container py-3">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="{{ route('frontoffice.home') }}">
            <img src="{{ asset('assets/images/logo/gls-noir.png') }}" alt="{{ config('app.name') }}" height="32" class="me-2">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#frontNav"
            aria-controls="frontNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="frontNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('frontoffice.home') ? 'active' : '' }}"
                        href="{{ route('frontoffice.home') }}">{{ __('Home') }}</a>
                </li>
                <li class="nav-item ms-lg-3">
                    {{-- Placeholder: student/parent portal login comes with the auth phase --}}
                    <a class="btn btn-primary" href="javascript:void(0);">{{ __('Student Portal') }}</a>
                </li>
            </ul>
        </div>
    </nav>
</header>
