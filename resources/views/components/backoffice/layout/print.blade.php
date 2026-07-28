{{--
    Backoffice print layout — bare shell for printable documents
    (receipts, invoices, reports). Theme CSS only, no JS, no chrome.

    Usage:
        <x-backoffice.layout.print :title="__('Receipt')">
            … printable content …
        </x-backoffice.layout.print>
--}}
@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ? $title.' — '.config('app.name') : config('app.name') }}</title>

    @if (app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('assets/preskool/css/bootstrap.rtl.min.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('assets/preskool/css/bootstrap.min.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/preskool/css/style.css') }}">
    @stack('styles')
</head>

<body>
    {{ $slot }}
</body>

</html>
