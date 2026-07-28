{{--
    Backoffice dashboard — visual source: theme-reference/preskool/dashboards/index.blade.php
    (PreSkool "Admin Dashboard").

    ⚠ All figures on this page are TEMPORARY UI PLACEHOLDERS to demonstrate the
    layout. They are hard-coded on purpose — no fake database models were created.
    Replace each block with real Domain data (via Livewire components) when the
    corresponding module is implemented.
--}}
<x-backoffice.layout.app :title="__('Dashboard')">

    <x-backoffice.layout.page-header
        :title="__('Dashboard')"
        :breadcrumbs="[__('Dashboard') => null]">
        <x-slot:actions>
            <div class="mb-2">
                <x-backoffice.ui.button href="javascript:void(0);" icon="ti ti-square-rounded-plus" class="me-3">
                    {{ __('Add New Student') }}
                </x-backoffice.ui.button>
            </div>
        </x-slot:actions>
    </x-backoffice.layout.page-header>

    {{-- Welcome banner (theme "Dashboard Content" card) --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card bg-dark">
                <div class="overlay-img">
                    <img src="{{ asset('assets/preskool/img/bg/shape-04.png') }}" alt="img" class="img-fluid shape-01">
                    <img src="{{ asset('assets/preskool/img/bg/shape-01.png') }}" alt="img" class="img-fluid shape-02">
                    <img src="{{ asset('assets/preskool/img/bg/shape-02.png') }}" alt="img" class="img-fluid shape-03">
                    <img src="{{ asset('assets/preskool/img/bg/shape-03.png') }}" alt="img" class="img-fluid shape-04">
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-xl-center justify-content-xl-between flex-xl-row flex-column">
                        <div class="mb-3 mb-xl-0">
                            <div class="d-flex align-items-center flex-wrap mb-2">
                                <h1 class="text-white me-2">{{ __('Welcome to GLS CRM') }}</h1>
                            </div>
                            <p class="text-white">{{ __('Have a good day at work') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI cards — real figures scoped to the active year + center, refreshed
         live when the top-bar context switcher changes. --}}
    @livewire('backoffice.dashboard.dashboard-stats')

</x-backoffice.layout.app>
