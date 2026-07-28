{{--
    Backoffice sidebar — adapted from PreSkool `layout/partials/sidebar.blade.php`.
    Markup pattern, classes and IDs preserved (#sidebar, .sidebar-inner.slimscroll,
    #sidebar-menu, .submenu-hdr, .menu-arrow, active states) so the theme CSS/JS
    (collapse, slimscroll, mini-sidebar) keep working.

    ONLY working pages are listed — add each module's entry here (gated with
    @can, linked via route()) when its screens ship. No dead javascript:void(0)
    placeholders.

    Every entry is gated with @can/@canany — UI convenience only: routes and
    controllers enforce the same permissions server-side (CLAUDE.md §16).

    Reference for submenu markup (collapsible groups):
    resources/views/theme-reference/preskool/layouts/partials/sidebar.blade.php
--}}
<div class="sidebar" id="sidebar">
    <div class="sidebar-inner slimscroll">
        <div id="sidebar-menu" class="sidebar-menu">

            {{-- <ul>
                <li>
                    <a href="{{ route('backoffice.dashboard') }}" class="d-flex align-items-center border bg-white rounded p-2 mb-4">
                        <img src="{{ asset('assets/preskool/img/icons/global-img.svg') }}" class="avatar avatar-md img-fluid rounded" alt="GLS">
                        <span class="text-dark ms-2 fw-normal">GLS CRM</span>
                    </a>
                </li>
            </ul> --}}

            <ul>
                <li>
                    <h6 class="submenu-hdr"><span>{{ __('Main') }}</span></h6>
                    <ul>
                        <li class="{{ request()->routeIs('backoffice.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('backoffice.dashboard') }}">
                                <i class="ti ti-layout-dashboard"></i><span>{{ __('Dashboard') }}</span>
                            </a>
                        </li>
                    </ul>
                </li>

                @canany(['students.view', 'employees.view'])
                    <li>
                        <h6 class="submenu-hdr"><span>{{ __('People') }}</span></h6>
                        <ul>
                            @can('students.view')
                                <li class="{{ request()->routeIs('backoffice.students.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.students.index') }}">
                                        <i class="ti ti-school"></i><span>{{ __('Students') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('employees.view')
                                <li class="{{ request()->routeIs('backoffice.employees.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.employees.index') }}">
                                        <i class="ti ti-users"></i><span>{{ __('Employees') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany(['groups.view', 'registrations.view'])
                    <li>
                        <h6 class="submenu-hdr"><span>{{ __('Academic') }}</span></h6>
                        <ul>
                            @can('registrations.view')
                                <li class="{{ request()->routeIs('backoffice.inscriptions.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.inscriptions.index') }}">
                                        <i class="ti ti-clipboard-list"></i><span>{{ __('Registrations') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('groups.view')
                                <li class="{{ request()->routeIs('backoffice.groups.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.groups.index') }}">
                                        <i class="ti ti-users-group"></i><span>{{ __('Groups') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany

                @canany([
                    'cash-registers.view', 'payments.view', 'expenses.view',
                    'expense-types.view', 'refunds.view', 'cash-transfers.view',
                ])
                    <li>
                        <h6 class="submenu-hdr"><span>{{ __('Finance') }}</span></h6>
                        <ul>
                            {{-- One entry for the tabbed Gestion de la caisse page
                                 (ma caisse + journal + transferts + comptes). --}}
                            @canany(['cash-registers.view', 'cash-transfers.view'])
                                <li class="{{ request()->routeIs('backoffice.caisses.*', 'backoffice.caisse-transfers.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.caisses.index') }}">
                                        <i class="ti ti-cash"></i><span>{{ __('Cash management') }}</span>
                                    </a>
                                </li>
                            @endcanany
                            @can('payments.view')
                                <li class="{{ request()->routeIs('backoffice.encaissements.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.encaissements.index') }}">
                                        <i class="ti ti-cash-banknote"></i><span>{{ __('Payments') }}</span>
                                    </a>
                                </li>
                            @endcan
                            {{-- One entry for the tabbed Gestion des dépenses page
                                 (dépenses + remboursements + types de dépenses). --}}
                            @canany(['expenses.view', 'refunds.view', 'expense-types.view'])
                                <li class="{{ request()->routeIs('backoffice.depenses.*', 'backoffice.types-depenses.*', 'backoffice.remboursements.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.depenses.index') }}">
                                        <i class="ti ti-receipt"></i><span>{{ __('Expense management') }}</span>
                                    </a>
                                </li>
                            @endcanany
                        </ul>
                    </li>
                @endcanany

                @canany(['users.view', 'roles.view', 'centers.view', 'academic-years.view', 'rooms.view'])
                    <li>
                        <h6 class="submenu-hdr"><span>{{ __('Administration') }}</span></h6>
                        <ul>
                            @canany(['centers.view', 'academic-years.view', 'rooms.view'])
                                <li class="{{ request()->routeIs('backoffice.settings') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.settings') }}">
                                        <i class="ti ti-settings"></i><span>{{ __('Settings') }}</span>
                                    </a>
                                </li>
                            @endcanany
                            @can('users.view')
                                <li class="{{ request()->routeIs('backoffice.users.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.users.index') }}">
                                        <i class="ti ti-user-cog"></i><span>{{ __('Users') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('roles.view')
                                <li class="{{ request()->routeIs('backoffice.roles.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.roles.index') }}">
                                        <i class="ti ti-shield-lock"></i><span>{{ __('Roles & Permissions') }}</span>
                                    </a>
                                </li>
                            @endcan
                            @can('permissions.view')
                                <li class="{{ request()->routeIs('backoffice.permissions.*') ? 'active' : '' }}">
                                    <a href="{{ route('backoffice.permissions.index') }}">
                                        <i class="ti ti-key"></i><span>{{ __('Permissions') }}</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcanany
            </ul>

        </div>
    </div>
</div>
