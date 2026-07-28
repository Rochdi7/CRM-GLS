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

{{-- Core plugins used across the admin --}}
<script src="{{ asset('assets/preskool/js/moment.js') }}"></script>
<script src="{{ asset('assets/preskool/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('assets/preskool/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/preskool/js/bootstrap-datetimepicker.min.js') }}"></script>

{{-- Feather icons + slimscroll (used by the sidebar) --}}
<script src="{{ asset('assets/preskool/js/feather.min.js') }}"></script>
<script src="{{ asset('assets/preskool/js/jquery.slimscroll.min.js') }}"></script>

{{-- Page-specific scripts (e.g. ApexCharts, DataTables, FullCalendar…) --}}
@stack('scripts')

{{-- PreSkool main behaviour script (sidebar toggle, mobile menu, dropdowns…) --}}
<script src="{{ asset('assets/preskool/js/script.js') }}"></script>
