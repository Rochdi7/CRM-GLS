{{--
    Backoffice guest layout (login, password reset, errors…).
    Mirrors PreSkool's `account-page` body variant: no header, no sidebar.

    Usage:
        <x-backoffice.layout.guest :title="__('Login')">
            … account page content (see theme-reference/preskool/authentication/) …
        </x-backoffice.layout.guest>
--}}
@props(['title' => null, 'bodyClass' => 'account-page'])

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

<body class="{{ $bodyClass }}">

    <div class="main-wrapper">
        {{ $slot }}
    </div>

    <x-backoffice.layout.scripts />
</body>

</html>
