<x-backoffice.layout.app :title="__('Expense management')">
    {{-- bootstrap-tagsinput: the "Mots-clés" chips field in the expense modal --}}
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/preskool/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('assets/preskool/plugins/bootstrap-tagsinput/bootstrap-tagsinput.min.js') }}"></script>
    @endpush

    <x-backoffice.layout.page-header
        :title="__('Expense management')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Expense management') => null]" />

    @php
        // Tab set mirrors the Paramètres page pattern: one tab per module,
        // shown only when its own view permission is held. `?tab=` deep-links
        // (used by the legacy remboursements redirect); otherwise the first
        // allowed tab is active. Types de dépenses moved to its own Inertia
        // page in Phase 6 (docs/phase-6-simple-crud-inventory.md §Q2) — no
        // longer a tab here.
        $tabs = collect([
            'depenses' => ['perm' => 'expenses.view', 'label' => __('Expenses'), 'icon' => 'ti ti-receipt'],
            'remboursements' => ['perm' => 'refunds.view', 'label' => __('Refunds'), 'icon' => 'ti ti-arrow-back-up'],
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
            </div>
        </div>
    </x-backoffice.ui.card>
</x-backoffice.layout.app>
