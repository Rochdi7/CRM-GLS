<x-backoffice.layout.app :title="__('Expense management')">
    <x-backoffice.layout.page-header
        :title="__('Expense management')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Expense management') => null]" />

    @php
        // Tab set mirrors the Paramètres page pattern: one tab per module,
        // shown only when its own view permission is held. `?tab=` deep-links
        // (used by the legacy remboursements / types-depenses redirects);
        // otherwise the first allowed tab is active.
        $tabs = collect([
            'depenses' => ['perm' => 'expenses.view', 'label' => __('Expenses'), 'icon' => 'ti ti-receipt'],
            'remboursements' => ['perm' => 'refunds.view', 'label' => __('Refunds'), 'icon' => 'ti ti-arrow-back-up'],
            'types' => ['perm' => 'expense-types.view', 'label' => __('Expense types'), 'icon' => 'ti ti-receipt-tax'],
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
                @can('expenses.view')
                    <div class="tab-pane fade {{ $first === 'depenses' ? 'show active' : '' }}" id="tab-depenses" role="tabpanel">
                        @livewire('backoffice.depenses.depenses-index')
                    </div>
                @endcan
                @can('refunds.view')
                    <div class="tab-pane fade {{ $first === 'remboursements' ? 'show active' : '' }}" id="tab-remboursements" role="tabpanel">
                        @livewire('backoffice.remboursements.remboursements-index')
                    </div>
                @endcan
                @can('expense-types.view')
                    <div class="tab-pane fade {{ $first === 'types' ? 'show active' : '' }}" id="tab-types" role="tabpanel">
                        @livewire('backoffice.types-depenses.types-depenses-index')
                    </div>
                @endcan
            </div>
        </div>
    </x-backoffice.ui.card>
</x-backoffice.layout.app>
