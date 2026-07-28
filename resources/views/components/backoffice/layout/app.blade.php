{{--
    Backoffice main layout — adapted from PreSkool `layout/mainlayout.blade.php`.

    Usage:
        <x-backoffice.layout.app :title="__('Dashboard')">
            <x-backoffice.layout.page-header :title="__('Dashboard')" :breadcrumbs="[...]" />
            … page content …
        </x-backoffice.layout.app>

    Notes:
    - The theme's Route::is() body-class switching was removed: this layout is
      ALWAYS the authenticated admin shell. Auth/error pages use
      <x-backoffice.layout.guest> instead.
    - data-theme / data-sidebar / … attributes are restored from localStorage by
      resources/js/backoffice/theme.js (same behaviour as the original theme).
    - Livewire 4 auto-injects its scripts + bundled Alpine. Never add Alpine
      manually (see CLAUDE.md).
--}}
@props(['title' => null, 'bodyClass' => ''])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    <x-backoffice.layout.head />
</head>

<body @class([$bodyClass => $bodyClass !== ''])>

    {{-- Page loader (faded out by PreSkool script.js) --}}
    <div id="global-loader">
        <div class="page-loader"></div>
    </div>

    {{-- Main Wrapper --}}
    <div class="main-wrapper">

        <x-backoffice.layout.header />
        <x-backoffice.layout.sidebar />

        <div class="page-wrapper">
            <div class="content">
                {{ $slot }}
            </div>
            <x-backoffice.layout.footer />
        </div>

        <x-backoffice.layout.theme-settings />
    </div>
    {{-- /Main Wrapper --}}

    <x-backoffice.layout.toasts />

    <x-backoffice.layout.scripts />
</body>

</html>
