{{--
    Shared shell for every HTTP error page.

    Deliberately standalone Blade, NOT the Inertia root shell: an error page
    must render when the app itself is failing, so it loads no Vite bundle and
    no React — a broken build or a boot-time exception would otherwise turn the
    error page into a second error. Only the static PreSkool CSS is used
    (CLAUDE.md §12), so the page still looks like GLS.

    The wording is the point (reported 31/08/2026): a bare "500 Erreur serveur"
    made users report the whole server as DOWN. Each page therefore says who is
    affected, whether the rest of the app still works, and what to do next.
--}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name', 'GLS CRM') }}</title>

    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('assets/images/favicon/favicon-96x96.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    @if (app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('assets/crm-gls/css/bootstrap.rtl.min.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/crm-gls/css/bootstrap.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/crm-gls/plugins/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/crm-gls/css/style.css') }}">
</head>
<body class="bg-white">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-6 col-xl-7 col-lg-8 col-md-10">
                <div class="d-flex flex-column justify-content-between min-vh-100 py-4">

                    <div class="text-center pt-3">
                        <img src="{{ asset('assets/images/logo/gls-noir.png') }}"
                             alt="{{ config('app.name', 'GLS CRM') }}"
                             style="max-height:48px;width:auto">
                    </div>

                    <div class="text-center px-3">
                        @hasSection('illustration')
                            <div class="mb-4">
                                <img src="@yield('illustration')" class="error-img img-fluid" alt=""
                                     style="max-height:260px">
                            </div>
                        @endif

                        <h1 class="fs-24 fw-bold mb-3">@yield('heading')</h1>

                        <p class="fs-16 text-muted mb-2">@yield('message')</p>

                        @hasSection('reassurance')
                            <p class="fs-14 text-muted mb-4">@yield('reassurance')</p>
                        @else
                            <div class="mb-4"></div>
                        @endif

                        <div class="d-flex flex-wrap gap-2 justify-content-center">
                            @yield('actions')
                        </div>

                        @hasSection('meta')
                            <div class="mt-4">
                                @yield('meta')
                            </div>
                        @endif
                    </div>

                    <div class="text-center pb-2">
                        <p class="fs-13 text-muted mb-0">
                            &copy; {{ date('Y') }} {{ config('app.name', 'GLS CRM') }}
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
