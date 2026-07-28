<x-backoffice.layout.app :title="__('Cash management')">
    <x-backoffice.layout.page-header
        :title="__('Cash management')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Cash management') => null]" />

    @php
        // Same pattern as the Gestion des dépenses / Paramètres pages:
        // one tab per module, shown only when its permission is held,
        // `?tab=` deep-links (used by the legacy caisse-transfers redirect).
        $tabs = collect([
            'ma-caisse' => ['perm' => 'cash-registers.view', 'label' => __('My cash register'), 'icon' => 'ti ti-wallet'],
            'journal' => ['perm' => 'cash-registers.view', 'label' => __('Transactions journal'), 'icon' => 'ti ti-report-money'],
            'transferts' => ['perm' => 'cash-transfers.view', 'label' => __('Cash Transfers'), 'icon' => 'ti ti-arrows-exchange'],
            'comptes' => ['perm' => 'cash-registers.view', 'label' => __('Cash accounts'), 'icon' => 'ti ti-cash'],
        ])->filter(fn ($t) => auth()->user()->can($t['perm']));
        $requested = request()->query('tab');
        $first = $tabs->has($requested) ? $requested : $tabs->keys()->first();
    @endphp

    <x-backoffice.ui.card flush>
        <div class="card-body">
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
                @can('cash-registers.view')
                    <div class="tab-pane fade {{ $first === 'ma-caisse' ? 'show active' : '' }}" id="tab-ma-caisse" role="tabpanel">
                        @livewire('backoffice.caisses.caisse-journal', ['scope' => 'mine'], key('cj-mine'))
                    </div>
                    <div class="tab-pane fade {{ $first === 'journal' ? 'show active' : '' }}" id="tab-journal" role="tabpanel">
                        @livewire('backoffice.caisses.caisse-journal', ['scope' => 'all'], key('cj-all'))
                    </div>
                @endcan
                @can('cash-transfers.view')
                    <div class="tab-pane fade {{ $first === 'transferts' ? 'show active' : '' }}" id="tab-transferts" role="tabpanel">
                        @livewire('backoffice.caisse-transfers.caisse-transfers-index')
                    </div>
                @endcan
                @can('cash-registers.view')
                    <div class="tab-pane fade {{ $first === 'comptes' ? 'show active' : '' }}" id="tab-comptes" role="tabpanel">
                        @livewire('backoffice.caisses.caisses-index')
                    </div>
                @endcan
            </div>
        </div>
    </x-backoffice.ui.card>
</x-backoffice.layout.app>
