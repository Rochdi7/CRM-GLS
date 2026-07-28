<x-frontoffice.layout.app :title="__('Home')">

    {{-- Hero --}}
    <section class="py-5">
        <div class="container py-5 text-center">
            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 mb-3">GLS SPRACHENZENTRUM BACKOFFICE</span>
            <h1 class="display-5 fw-bold mb-3">{{ __('Welcome to GLS') }}</h1>
            <p class="lead text-muted mx-auto mb-4" style="max-width: 40rem;">
                {{ __('Language courses, student portal and school services — all in one place.') }}
            </p>
            <div class="d-flex justify-content-center gap-2">
                {{-- Placeholder actions; real destinations come with the auth phase --}}
                <a href="javascript:void(0);" class="btn btn-primary btn-lg">{{ __('Student Portal') }}</a>
                <a href="javascript:void(0);" class="btn btn-outline-secondary btn-lg">{{ __('Our Courses') }}</a>
            </div>
        </div>
    </section>

    {{-- Feature cards --}}
    <section class="pb-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <span class="badge bg-primary rounded-circle p-3 mb-3"><i class="ti ti-language fs-4"></i></span>
                            <h5>{{ __('Language Courses') }}</h5>
                            <p class="text-muted mb-0">{{ __('French, Arabic, English and German programs for all levels.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <span class="badge bg-success rounded-circle p-3 mb-3"><i class="ti ti-school fs-4"></i></span>
                            <h5>{{ __('Student Portal') }}</h5>
                            <p class="text-muted mb-0">{{ __('Follow your registrations, schedule and results online.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-4">
                            <span class="badge bg-info rounded-circle p-3 mb-3"><i class="ti ti-calendar-check fs-4"></i></span>
                            <h5>{{ __('Easy Registration') }}</h5>
                            <p class="text-muted mb-0">{{ __('Register to a group in minutes and pay at the center.') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</x-frontoffice.layout.app>
