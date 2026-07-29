{{--
    Backoffice footer scripts — adapted from PreSkool `layout/partials/footer-scripts.blade.php`.

    Strategy:
    - The theme's per-route @if(Route::is([...])) conditions were replaced by:
        * a small "always loaded" core, and
        * @stack('scripts') for page-specific plugins (push from the page).
    - jQuery-based theme plugins stay as classic static <script> tags (they are not
      ES modules). Vite only manages our own code (resources/js/backoffice/app.js,
      loaded from the <head> component as a deferred module).
    - Livewire 4 injects its own scripts (and its bundled Alpine) automatically.
      NEVER add Alpine or @livewireScripts manually — Alpine must exist only once.
--}}

{{-- jQuery (required by the PreSkool theme plugins) --}}
<script src="{{ asset('assets/preskool/js/jquery-3.7.1.min.js') }}"></script>

{{-- Bootstrap 5 bundle (includes Popper) --}}
<script src="{{ asset('assets/preskool/js/bootstrap.bundle.min.js') }}"></script>

{{-- Select2 — used by CRUD dropdowns across 15+ pages, stays global. --}}
<script src="{{ asset('assets/preskool/plugins/select2/js/select2.min.js') }}"></script>

{{-- slimscroll (used by the sidebar) --}}
<script src="{{ asset('assets/preskool/js/jquery.slimscroll.min.js') }}"></script>

{{-- Page-specific scripts: moment, date-range/date-time pickers, Feather,
     ApexCharts, DataTables, FullCalendar… none of moment/the pickers/Feather
     is used anywhere today (grep-verified zero usages) so they moved out of
     the global shell — @push them from the page when a screen actually needs
     one. --}}
@stack('scripts')

{{-- Feather stub: script.js:13 calls feather.replace() unconditionally on
     every page. We removed feather.min.js (0 data-feather nodes exist in our
     views — it replaced nothing), so this no-op keeps that call from
     throwing and aborting the rest of script.js's ready handler. Must load
     before script.js (inline, not the deferred Vite module). --}}
<script>window.feather = window.feather || { replace: function () {} };</script>

{{-- PreSkool main behaviour script (sidebar toggle, mobile menu, dropdowns…) --}}
<script src="{{ asset('assets/preskool/js/script.js') }}"></script>
