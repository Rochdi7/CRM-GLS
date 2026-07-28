{{--
    Backoffice login — visual source:
    theme-reference/preskool/authentication/login-3.blade.php
    (centered single-column variant, no side image panel)

    Adaptations (reference file untouched):
    - <x-backoffice.layout.guest> instead of the theme mainlayout
    - asset('assets/preskool/…') paths
    - removed demo social buttons, "Or" divider, register link
    - real POST to backoffice.login.store with CSRF, old() and @error rendering
    - password visibility toggle (.toggle-password) is handled by theme script.js
--}}
<x-backoffice.layout.guest :title="__('Sign In')" bodyClass="account-page">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 mx-auto">
                <form method="POST" action="{{ route('backoffice.login.store') }}">
                    @csrf
                    <div class="d-flex flex-column justify-content-between vh-100">

                        <div class="mx-auto p-4 text-center">
                            {{-- GLS logo — black in light mode, white in dark mode
                                 (.gls-logo-* classes defined in resources/scss/backoffice/app.scss) --}}
                            <img src="{{ asset('assets/images/logo/gls-noir.png') }}"
                                class="img-fluid gls-auth-logo gls-logo-light" alt="{{ config('app.name') }}">
                            <img src="{{ asset('assets/images/logo/gls-blanc.webp') }}"
                                class="img-fluid gls-auth-logo gls-logo-dark" alt="{{ config('app.name') }}">
                        </div>

                        <div class="card">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h2 class="mb-2">{{ __('Welcome') }}</h2>
                                    <p class="mb-0">{{ __('Please enter your details to sign in') }}</p>
                                </div>

                                @if (session('status'))
                                    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label" for="login">{{ __('Email or username') }}</label>
                                    <div class="input-icon mb-3 position-relative">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-user"></i>
                                        </span>
                                        <input type="text" id="login" name="login" value="{{ old('login') }}"
                                            class="form-control @error('login') is-invalid @enderror"
                                            required autofocus autocomplete="username">
                                        @error('login')
                                            <div class="text-danger pt-2">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <label class="form-label" for="password">{{ __('Password') }}</label>
                                    <div class="pass-group">
                                        <input type="password" id="password" name="password"
                                            class="pass-input form-control @error('password') is-invalid @enderror"
                                            required autocomplete="current-password">
                                        <span class="ti toggle-password ti-eye-off"></span>
                                        @error('password')
                                            <div class="text-danger pt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-wrap form-wrap-checkbox mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="form-check form-check-md mb-0">
                                            <input class="form-check-input mt-0" type="checkbox"
                                                id="remember" name="remember" value="1">
                                        </div>
                                        <p class="ms-1 mb-0">
                                            <label for="remember">{{ __('Remember Me') }}</label>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <a href="{{ route('backoffice.password.request') }}" class="link-danger">{{ __('Forgot Password?') }}</a>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary w-100">{{ __('Sign In') }}</button>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 text-center">
                            <p class="mb-0">{{ __('Copyright') }} &copy; {{ now()->year }} — {{ config('app.name') }}</p>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

</x-backoffice.layout.guest>
