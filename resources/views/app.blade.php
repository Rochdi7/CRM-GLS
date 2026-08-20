<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'GLS CRM') }}</title>

    {{--
        Same static asset strategy as the Blade/Livewire shell
        (components/backoffice/layout/head.blade.php) — one Bootstrap CSS
        instance, one set of icon fonts, never duplicated via npm imports
        (docs/inertia-react-migration-audit.md §6.8/§6.10).
    --}}
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('assets/images/favicon/favicon-96x96.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon/favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    @if (app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('assets/crm-gls/css/bootstrap.rtl.min.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/crm-gls/css/bootstrap.min.css') }}">
    @endif

    <link rel="stylesheet" href="{{ asset('assets/crm-gls/plugins/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/crm-gls/plugins/fontawesome/css/all.min.css') }}">
    {{-- Select2 CSS only (no jQuery/Select2 JS anywhere): SelectField.tsx
         renders Select2's own markup as a React-native searchable dropdown. --}}
    <link rel="stylesheet" href="{{ asset('assets/crm-gls/css/select2.min.css') }}">
    {{-- Flag sprite classes (.flag.flag-xx, ISO-3166 lowercase codes) used by
         PhoneField's country dropdown — see icons/icon-flag.blade.php reference. --}}
    <link rel="stylesheet" href="{{ asset('assets/crm-gls/plugins/icons/flags/flags.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/crm-gls/css/style.css') }}">

    {{--
        No @routes/Ziggy yet — not installed (migration plan §"Routing":
        only add it once a page actually needs client-side named-route
        generation; the pilot page does not).
    --}}
    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
