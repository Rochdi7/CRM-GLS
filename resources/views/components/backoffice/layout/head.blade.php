{{--
    Backoffice <head> assets — adapted from PreSkool `layout/partials/head.blade.php`.

    Strategy:
    - The theme's per-route @if(Route::is([...])) conditions were replaced by:
        * a small "always loaded" core (what every backoffice page needs), and
        * @stack('styles') for page-specific plugin CSS (push from the page).
    - All assets are static copies served from public/assets/preskool/ (see CLAUDE.md).
    - RTL: when the locale is Arabic the RTL Bootstrap build is loaded instead.
--}}

{{-- Favicon (GLS brand set) --}}
<link rel="icon" type="image/png" sizes="96x96" href="{{ asset('assets/images/favicon/favicon-96x96.png') }}">
<link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/favicon/favicon.svg') }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/favicon/apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('assets/images/favicon/site.webmanifest') }}">

{{-- Bootstrap CSS (RTL build for Arabic) --}}
@if (app()->getLocale() === 'ar')
    <link rel="stylesheet" href="{{ asset('assets/preskool/css/bootstrap.rtl.min.css') }}">
@else
    <link rel="stylesheet" href="{{ asset('assets/preskool/css/bootstrap.min.css') }}">
@endif

{{-- Icon fonts (core). fontawesome.min.css is deliberately NOT loaded here:
     all.min.css is a strict superset (adds FA5+FA7 Brands, same Free family)
     — loading both duplicated ~56 KB for nothing. Feather is not loaded
     globally: zero data-feather nodes exist in our views (see app.js stub). --}}
<link rel="stylesheet" href="{{ asset('assets/preskool/plugins/tabler-icons/tabler-icons.css') }}">
<link rel="stylesheet" href="{{ asset('assets/preskool/plugins/fontawesome/css/all.min.css') }}">

{{-- Select2 — used by CRUD dropdowns across 15+ pages, stays global. --}}
<link rel="stylesheet" href="{{ asset('assets/preskool/plugins/select2/css/select2.min.css') }}">

{{-- Page-specific styles: date-range/date-time pickers, Feather, DataTables,
     FullCalendar, Summernote… none of the pickers are used anywhere today
     (grep-verified) so they moved out of the global shell — @push them from
     the page when a screen actually needs one. --}}
@stack('styles')

{{-- PreSkool main theme CSS — must come after plugins --}}
<link rel="stylesheet" href="{{ asset('assets/preskool/css/style.css') }}">

{{-- Vite-managed backoffice overrides (loaded last so they win) --}}
@vite(['resources/scss/backoffice/app.scss', 'resources/js/backoffice/app.js'])
