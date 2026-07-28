{{--
    Read-only expense detail + receipts gallery.
    Data comes from DepenseController@show: $depense->load(['typeDepense', 'caisse', 'agent']).
    No delete action anywhere — an expense is never deleted (audit trail).
--}}
<x-backoffice.layout.app :title="$depense->reference">
    <x-backoffice.layout.page-header
        :title="$depense->reference"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Expenses') => route('backoffice.depenses.index'),
            $depense->reference => null,
        ]">
        @can('update', $depense)
            <x-slot:actions>
                <a href="{{ route('backoffice.depenses.index') }}" class="btn btn-light d-flex align-items-center">
                    <i class="ti ti-arrow-left me-2"></i>{{ __('Back to list') }}
                </a>
            </x-slot:actions>
        @endcan
    </x-backoffice.layout.page-header>

    <div class="row">
        {{-- Summary --}}
        <div class="col-xl-4">
            <x-backoffice.ui.card>
                <div class="text-center mb-3">
                    <span class="avatar avatar-xxl rounded-circle bg-danger-transparent d-inline-flex align-items-center justify-content-center mb-2">
                        <i class="ti ti-cash-banknote fs-24 text-danger"></i>
                    </span>
                    <h5 class="mb-1 text-danger">- {{ number_format((float) $depense->montant, 2) }} MAD</h5>
                    <p class="text-muted mb-2"><code>{{ $depense->reference }}</code></p>
                    @if ($depense->typeDepense)
                        <x-backoffice.ui.badge variant="info">{{ $depense->typeDepense->nom }}</x-backoffice.ui.badge>
                    @endif
                </div>
                <div class="border-top pt-3">
                    @foreach ([
                        __('Expense date') => $depense->date_depense?->format('d/m/Y'),
                        __('Cash register') => $depense->caisse?->nom,
                        __('Center') => $depense->caisse?->etablissement?->nom_centre,
                        __('Recorded by') => $depense->agent?->nomComplet(),
                        __('Recorded on') => $depense->created_at?->format('d/m/Y H:i'),
                    ] as $label => $value)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ $label }}</span>
                            <span class="fw-medium text-end ms-2 text-truncate">{{ $value ?: '—' }}</span>
                        </div>
                    @endforeach
                </div>
            </x-backoffice.ui.card>
        </div>

        <div class="col-xl-8">
            {{-- Details --}}
            <x-backoffice.ui.card :title="__('Details')">
                <div class="mb-3">
                    <span class="text-muted d-block mb-1">{{ __('Description') }}</span>
                    <span class="fw-medium">{{ $depense->description ?: '—' }}</span>
                </div>
                <div class="mb-3">
                    <span class="text-muted d-block mb-1">{{ __('Keywords') }}</span>
                    @if ($depense->mots_cles)
                        @foreach (array_filter(array_map('trim', explode(',', $depense->mots_cles))) as $mot)
                            <x-backoffice.ui.badge variant="secondary" class="me-1">{{ $mot }}</x-backoffice.ui.badge>
                        @endforeach
                    @else
                        <span class="fw-medium">—</span>
                    @endif
                </div>
                <div>
                    <span class="text-muted d-block mb-1">{{ __('Note') }}</span>
                    <span class="fw-medium">{{ $depense->note ?: '—' }}</span>
                </div>
            </x-backoffice.ui.card>

            {{-- Receipts gallery — URLs are /media/<8-char-uuid>/… (never /storage/…) --}}
            @php ($justificatifs = $depense->getMedia('justificatifs'))
            <x-backoffice.ui.card :title="__('Receipts')">
                <x-slot:tools>
                    <x-backoffice.ui.badge variant="secondary">{{ $justificatifs->count() }}</x-backoffice.ui.badge>
                </x-slot:tools>
                @if ($justificatifs->isEmpty())
                    <x-backoffice.ui.empty-state :title="__('No receipts attached')"
                        :message="__('This expense has no supporting document.')" icon="ti ti-paperclip" />
                @else
                    <div class="row g-3">
                        @foreach ($justificatifs as $media)
                            <div class="col-md-4 col-sm-6">
                                <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener"
                                    class="d-block border rounded overflow-hidden text-decoration-none">
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 140px;">
                                        @if (str_starts_with((string) $media->mime_type, 'image/'))
                                            <img src="{{ $media->getUrl() }}" alt="{{ $media->file_name }}"
                                                class="w-100 h-100" style="object-fit: cover;">
                                        @else
                                            <i class="ti ti-file-type-pdf fs-24 text-danger"></i>
                                        @endif
                                    </div>
                                    <div class="p-2">
                                        <span class="d-block text-truncate fs-13 text-dark">{{ $media->file_name }}</span>
                                        <span class="text-muted fs-12">{{ number_format($media->size / 1024, 0) }} KB</span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-backoffice.ui.card>
        </div>
    </div>
</x-backoffice.layout.app>
