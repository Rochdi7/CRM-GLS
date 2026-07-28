<x-backoffice.layout.app :title="__('Settings')">
    <x-backoffice.layout.page-header
        :title="__('Settings')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Settings') => null]" />

    @php
        // First tab the user is allowed to see becomes the active one.
        $tabs = collect([
            'etablissements' => ['perm' => 'centers.view', 'label' => __('Centers'), 'icon' => 'ti ti-building'],
            'annees' => ['perm' => 'academic-years.view', 'label' => __('Academic Years'), 'icon' => 'ti ti-calendar'],
            'salles' => ['perm' => 'rooms.view', 'label' => __('Rooms'), 'icon' => 'ti ti-door'],
            'frais' => ['perm' => 'fees.view', 'label' => __('Fees'), 'icon' => 'ti ti-receipt'],
        ])->filter(fn ($t) => auth()->user()->can($t['perm']));
        $first = $tabs->keys()->first();
    @endphp

    <x-backoffice.ui.card flush>
        <div class="card-body">
            {{-- Tab nav (PreSkool / Bootstrap pills) --}}
            <ul class="nav nav-tabs nav-tabs-solid mb-4" role="tablist">
                @foreach ($tabs as $key => $tab)
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $key === $first ? 'active' : '' }}"
                            data-bs-toggle="tab" href="#tab-{{ $key }}" role="tab">
                            <i class="{{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @can('centers.view')
                    <div class="tab-pane fade {{ $first === 'etablissements' ? 'show active' : '' }}" id="tab-etablissements" role="tabpanel">
                        @livewire('backoffice.settings.etablissements-tab')
                    </div>
                @endcan
                @can('academic-years.view')
                    <div class="tab-pane fade {{ $first === 'annees' ? 'show active' : '' }}" id="tab-annees" role="tabpanel">
                        @livewire('backoffice.settings.annees-scolaires-tab')
                    </div>
                @endcan
                @can('rooms.view')
                    <div class="tab-pane fade {{ $first === 'salles' ? 'show active' : '' }}" id="tab-salles" role="tabpanel">
                        @livewire('backoffice.settings.salles-tab')
                    </div>
                @endcan
                @can('fees.view')
                    <div class="tab-pane fade {{ $first === 'frais' ? 'show active' : '' }}" id="tab-frais" role="tabpanel">
                        @livewire('backoffice.settings.frais-tab')
                    </div>
                @endcan
            </div>
        </div>
    </x-backoffice.ui.card>
</x-backoffice.layout.app>
