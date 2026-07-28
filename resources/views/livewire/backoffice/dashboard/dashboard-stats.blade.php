<div>
    {{-- Context banner --}}
    <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
        <span class="text-muted">{{ __('Showing data for') }}:</span>
        <span class="badge badge-soft-primary"><i class="ti ti-calendar me-1"></i>{{ $anneeLabel ?? '—' }}</span>
        <span class="badge badge-soft-info"><i class="ti ti-building me-1"></i>{{ $centreLabel ?? __('All centers') }}</span>
    </div>

    <div class="row">
        {{-- Students (scoped by center) --}}
        <div class="col-xxl-3 col-sm-6 d-flex">
            <div class="card flex-fill border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-xl bg-danger-transparent me-2 p-1">
                            <img src="{{ asset('assets/preskool/img/icons/student.svg') }}" alt="img">
                        </div>
                        <div class="overflow-hidden flex-fill">
                            <h2 class="counter">{{ $studentsTotal }}</h2>
                            <p>{{ __('Students') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Employees (scoped by center) --}}
        <div class="col-xxl-3 col-sm-6 d-flex">
            <div class="card flex-fill border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-xl me-2 bg-secondary-transparent p-1">
                            <img src="{{ asset('assets/preskool/img/icons/teacher.svg') }}" alt="img">
                        </div>
                        <div class="overflow-hidden flex-fill">
                            <h2 class="counter">{{ $employeesTotal }}</h2>
                            <p class="text-gray">{{ __('Employees') }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between border-top mt-3 pt-3">
                        <p class="mb-0">{{ __('Active') }} : <span class="text-dark fw-semibold">{{ $employeesActive }}</span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Groups (scoped by year + center) --}}
        <div class="col-xxl-3 col-sm-6 d-flex">
            <div class="card flex-fill border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-xl me-2 bg-warning-transparent p-1">
                            <img src="{{ asset('assets/preskool/img/icons/staff.svg') }}" alt="img">
                        </div>
                        <div class="overflow-hidden flex-fill">
                            <h2 class="counter">{{ $groupsTotal }}</h2>
                            <p class="text-gray">{{ __('Groups') }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between border-top mt-3 pt-3">
                        <p class="mb-0">{{ __('En formation') }} : <span class="text-dark fw-semibold">{{ $groupsEnFormation }}</span></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payments this month (scoped by center) --}}
        <div class="col-xxl-3 col-sm-6 d-flex">
            <div class="card flex-fill border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-xl me-2 bg-success-transparent p-1">
                            <img src="{{ asset('assets/preskool/img/icons/subject.svg') }}" alt="img">
                        </div>
                        <div class="overflow-hidden flex-fill">
                            <h2 class="counter">{{ $inscriptionsActives }}</h2>
                            <p class="text-gray">{{ __('Active registrations') }}</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between border-top mt-3 pt-3">
                        <p class="mb-0">{{ __('This month') }} :
                            <span class="text-dark fw-semibold">{{ number_format($paymentsMonth, 2) }} MAD</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
