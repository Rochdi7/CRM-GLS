{{--
    Read-only cash register detail.

    CaisseController@show passes ONLY $caisse (with etablissement + responsable
    eager-loaded), so the recent-movement lists are queried here from the
    model's relations — the controller stays untouched.
--}}
@php
    $encaissements = $caisse->encaissements()->with('student')->latest('date_paiement')->limit(10)->get();
    $depenses = $caisse->depenses()->with('typeDepense')->latest('date_depense')->limit(10)->get();
    $remboursements = $caisse->remboursements()->with('beneficiaire')->latest('date_remboursement')->limit(10)->get();
    $transfers = \App\Models\CaisseTransfer::query()
        ->with(['caisseSource', 'caisseDestination'])
        ->where('caisse_source_id', $caisse->id)
        ->orWhere('caisse_destination_id', $caisse->id)
        ->latest('date_transfert')
        ->limit(10)
        ->get();
@endphp

<x-backoffice.layout.app :title="$caisse->nom">
    <x-backoffice.layout.page-header
        :title="$caisse->nom"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Cash Registers') => route('backoffice.caisses.index'),
            $caisse->nom => null,
        ]" />

    <div class="row">
        <div class="col-xl-4">
            <x-backoffice.ui.card :title="__('Cash Register')">
                <div class="text-center border-bottom pb-3 mb-3">
                    <p class="text-muted mb-1">{{ __('Balance') }}</p>
                    <h3 class="mb-0">{{ number_format((float) $caisse->solde, 2) }} DH</h3>
                </div>
                @foreach ([
                    __('Center') => $caisse->etablissement?->nom_centre,
                    __('Manager') => $caisse->responsable?->nomComplet(),
                ] as $label => $value)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="fw-medium text-end">{{ $value ?: '—' }}</span>
                    </div>
                @endforeach
                <div class="d-flex justify-content-between">
                    <span class="text-muted">{{ __('Status') }}</span>
                    <span class="badge {{ $caisse->statut === \App\Models\Caisse::STATUT_ACTIVE ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                        {{ $caisse->statut }}
                    </span>
                </div>
            </x-backoffice.ui.card>

            <x-backoffice.ui.alert variant="info" :dismissible="false">
                {{ __('The balance is maintained by the application: it only moves through payments, expenses, refunds and validated transfers.') }}
            </x-backoffice.ui.alert>
        </div>

        <div class="col-xl-8">
            {{-- Recent payments in --}}
            <x-backoffice.ui.card :title="__('Recent payments')">
                <x-slot:tools>
                    <span class="badge badge-soft-secondary">{{ $encaissements->count() }}</span>
                </x-slot:tools>
                @if ($encaissements->isEmpty())
                    <x-backoffice.ui.empty-state :title="__('No payments yet')" icon="ti ti-cash" />
                @else
                    <x-backoffice.ui.table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Student') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Method') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($encaissements as $enc)
                            <tr>
                                <td><code>{{ $enc->reference }}</code></td>
                                <td>{{ $enc->student?->nomComplet() ?? '—' }}</td>
                                <td>{{ $enc->date_paiement?->format('d/m/Y') }}</td>
                                <td><span class="badge badge-soft-info">{{ $enc->methode }}</span></td>
                                <td class="text-end fw-medium">{{ number_format((float) $enc->montant, 2) }} DH</td>
                            </tr>
                        @endforeach
                    </x-backoffice.ui.table>
                @endif
            </x-backoffice.ui.card>

            {{-- Recent expenses --}}
            <x-backoffice.ui.card :title="__('Recent expenses')">
                <x-slot:tools>
                    <span class="badge badge-soft-secondary">{{ $depenses->count() }}</span>
                </x-slot:tools>
                @if ($depenses->isEmpty())
                    <x-backoffice.ui.empty-state :title="__('No expenses yet')" icon="ti ti-receipt" />
                @else
                    <x-backoffice.ui.table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Expense type') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($depenses as $dep)
                            <tr>
                                <td><code>{{ $dep->reference }}</code></td>
                                <td>{{ $dep->typeDepense?->nom ?? '—' }}</td>
                                <td>{{ $dep->date_depense?->format('d/m/Y') }}</td>
                                <td class="text-end fw-medium">{{ number_format((float) $dep->montant, 2) }} DH</td>
                            </tr>
                        @endforeach
                    </x-backoffice.ui.table>
                @endif
            </x-backoffice.ui.card>

            {{-- Recent refunds --}}
            <x-backoffice.ui.card :title="__('Recent refunds')">
                <x-slot:tools>
                    <span class="badge badge-soft-secondary">{{ $remboursements->count() }}</span>
                </x-slot:tools>
                @if ($remboursements->isEmpty())
                    <x-backoffice.ui.empty-state :title="__('No refunds yet')" icon="ti ti-arrow-back-up" />
                @else
                    <x-backoffice.ui.table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Beneficiary') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($remboursements as $rmb)
                            <tr>
                                <td><code>{{ $rmb->reference }}</code></td>
                                <td>{{ $rmb->beneficiaire?->nomComplet() ?? '—' }}</td>
                                <td>{{ $rmb->date_remboursement?->format('d/m/Y') }}</td>
                                <td class="text-end fw-medium">{{ number_format((float) $rmb->montant, 2) }} DH</td>
                            </tr>
                        @endforeach
                    </x-backoffice.ui.table>
                @endif
            </x-backoffice.ui.card>

            {{-- Recent transfers (in + out) --}}
            <x-backoffice.ui.card :title="__('Recent transfers')">
                <x-slot:tools>
                    <span class="badge badge-soft-secondary">{{ $transfers->count() }}</span>
                </x-slot:tools>
                @if ($transfers->isEmpty())
                    <x-backoffice.ui.empty-state :title="__('No transfers yet')" icon="ti ti-transfer" />
                @else
                    <x-backoffice.ui.table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Direction') }}</th>
                                <th>{{ __('Counterpart') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Amount') }}</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($transfers as $trf)
                            @php $sortant = $trf->caisse_source_id === $caisse->id; @endphp
                            <tr>
                                <td><code>{{ $trf->reference }}</code></td>
                                <td>
                                    <span class="badge {{ $sortant ? 'badge-soft-danger' : 'badge-soft-success' }}">
                                        {{ $sortant ? __('Outgoing') : __('Incoming') }}
                                    </span>
                                </td>
                                <td>{{ $sortant ? $trf->caisseDestination?->nom : $trf->caisseSource?->nom }}</td>
                                <td>{{ $trf->date_transfert?->format('d/m/Y') }}</td>
                                <td><span class="badge badge-soft-info">{{ $trf->statut }}</span></td>
                                <td class="text-end fw-medium">{{ number_format((float) $trf->montant, 2) }} DH</td>
                            </tr>
                        @endforeach
                    </x-backoffice.ui.table>
                @endif
            </x-backoffice.ui.card>
        </div>
    </div>
</x-backoffice.layout.app>
