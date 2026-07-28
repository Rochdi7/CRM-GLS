{{--
    Backoffice "forgot password" — visual source:
    theme-reference/preskool/authentication/forgot-password-3.blade.php
    Adapted: guest layout, GLS logo, real POST to backoffice.password.email.
--}}
<x-backoffice.layout.guest :title="__('Forgot Password?')" bodyClass="account-page">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 mx-auto">
                <form method="POST" action="{{ route('backoffice.password.email') }}">
                    @csrf
                    <div class="d-flex flex-column justify-content-between vh-100">

                        <div class="mx-auto p-4 text-center">
                            <img src="{{ asset('assets/images/logo/gls-noir.png') }}"
                                class="img-fluid gls-auth-logo gls-logo-light" alt="{{ config('app.name') }}">
                            <img src="{{ asset('assets/images/logo/gls-blanc.webp') }}"
                                class="img-fluid gls-auth-logo gls-logo-dark" alt="{{ config('app.name') }}">
                        </div>

                        <div class="card">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h2 class="mb-2">{{ __('Forgot Password?') }}</h2>
                                    <p class="mb-0">{{ __('Enter your email and we will send you a link to reset your password.') }}</p>
                                </div>

                                @if (session('status'))
                                    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label" for="email">{{ __('Email Address') }}</label>
                                    <div class="input-icon mb-3 position-relative">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-mail"></i>
                                        </span>
                                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                                            class="form-control @error('email') is-invalid @enderror"
                                            required autofocus autocomplete="username">
                                        @error('email')
                                            <div class="text-danger pt-2">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary w-100">{{ __('Send Reset Link') }}</button>
                                </div>

                                <div class="text-center">
                                    <h6 class="fw-normal text-dark mb-0">
                                        <a href="{{ route('backoffice.login') }}" class="hover-a">
                                            <i class="ti ti-arrow-left me-1"></i>{{ __('Back to Sign In') }}
                                        </a>
                                    </h6>
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
