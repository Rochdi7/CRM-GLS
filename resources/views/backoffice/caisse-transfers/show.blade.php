{{--
    Read-only transfer detail. CaisseTransferController@show passes $transfer
    with caisseSource / caisseDestination / requestedBy / validatedBy loaded.
    No actions here: validation and cancellation live on the Livewire index
    (two-step workflow, structure doc §7).
--}}
<x-backoffice.layout.app :title="$transfer->reference">
    <x-backoffice.layout.page-header
        :title="__('Transfer').' '.$transfer->reference"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Cash Transfers') => route('backoffice.caisse-transfers.index'),
            $transfer->reference => null,
        ]" />

    <div class="row">
        {{-- Summary --}}
        <div class="col-xl-5">
            <x-backoffice.ui.card :title="__('Transfer')">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Reference') }}</span>
                    <span class="fw-medium">{{ $transfer->reference }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Source cash register') }}</span>
                    <span class="fw-medium">{{ $transfer->caisseSource?->nom ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Destination cash register') }}</span>
                    <span class="fw-medium">{{ $transfer->caisseDestination?->nom ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Amount') }}</span>
                    <span class="fw-semibold">{{ number_format((float) $transfer->montant, 2) }} DH</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Date') }}</span>
                    <span class="fw-medium">{{ $transfer->date_transfert?->format('d/m/Y H:i') ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">{{ __('Status') }}</span>
                    <span class="badge {{ $transfer->statut === \App\Models\CaisseTransfer::STATUT_VALIDE ? 'badge-soft-success' : ($transfer->statut === \App\Models\CaisseTransfer::STATUT_ANNULE ? 'badge-soft-secondary' : 'badge-soft-warning') }}">
                        {{ $transfer->statut }}
                    </span>
                </div>
            </x-backoffice.ui.card>

            {{-- Two-person control trail --}}
            <x-backoffice.ui.card :title="__('Approval trail')">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Requested by') }}</span>
                    <span class="fw-medium">{{ $transfer->requestedBy?->nomComplet() ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">{{ __('Validated by') }}</span>
                    <span class="fw-medium">{{ $transfer->validatedBy?->nomComplet() ?? __('Not validated yet') }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">{{ __('Note') }}</span>
                    <span class="fw-medium text-end">{{ $transfer->note ?? '—' }}</span>
                </div>
            </x-backoffice.ui.card>
        </div>

        {{-- Balance snapshots --}}
        <div class="col-xl-7">
            <x-backoffice.ui.card :title="__('Balance snapshots')">
                @if ($transfer->statut === \App\Models\CaisseTransfer::STATUT_EN_ATTENTE)
                    <x-backoffice.ui.alert variant="warning" :dismissible="false">
                        {{ __('The balances have not moved yet — this transfer is still awaiting validation by a different employee.') }}
                    </x-backoffice.ui.alert>
                @endif

                <x-backoffice.ui.table>
                    <x-slot:head>
                        <tr>
                            <th>{{ __('Cash Register') }}</th>
                            <th class="text-end">{{ __('Balance before') }}</th>
                            <th class="text-end">{{ __('Balance after') }}</th>
                        </tr>
                    </x-slot:head>
                    <tr>
                        <td class="fw-medium">{{ $transfer->caisseSource?->nom ?? '—' }}</td>
                        <td class="text-end">
                            {{ $transfer->solde_source_avant === null ? '—' : number_format((float) $transfer->solde_source_avant, 2).' DH' }}
                        </td>
                        <td class="text-end">
                            {{ $transfer->solde_source_apres === null ? '—' : number_format((float) $transfer->solde_source_apres, 2).' DH' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="fw-medium">{{ $transfer->caisseDestination?->nom ?? '—' }}</td>
                        <td class="text-end">
                            {{ $transfer->solde_dest_avant === null ? '—' : number_format((float) $transfer->solde_dest_avant, 2).' DH' }}
                        </td>
                        <td class="text-end">
                            {{ $transfer->solde_dest_apres === null ? '—' : number_format((float) $transfer->solde_dest_apres, 2).' DH' }}
                        </td>
                    </tr>
                </x-backoffice.ui.table>
            </x-backoffice.ui.card>
        </div>
    </div>
</x-backoffice.layout.app>
