{{--
    Frontoffice guest layout — for future student/parent login and public
    standalone pages (no site header/footer).

    Usage:
        <x-frontoffice.layout.guest :title="__('Student Login')">
            … content …
        </x-frontoffice.layout.guest>
--}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    {{-- Favicon (GLS brand set) --}}
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('assets/images/favicon/favicon-96x96.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon/favicon.svg') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('assets/images/favicon/site.webmanifest') }}">

    @if (app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('assets/preskool/css/bootstrap.rtl.min.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/preskool/css/bootstrap.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/preskool/plugins/tabler-icons/tabler-icons.css') }}">

    @stack('styles')
    @vite(['resources/scss/frontoffice/app.scss', 'resources/js/frontoffice/app.js'])
</head>

<body class="d-flex flex-column min-vh-100 bg-light">

    <main class="flex-grow-1 d-flex align-items-center justify-content-center">
        {{ $slot }}
    </main>

    <script src="{{ asset('assets/preskool/js/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>

</html>
