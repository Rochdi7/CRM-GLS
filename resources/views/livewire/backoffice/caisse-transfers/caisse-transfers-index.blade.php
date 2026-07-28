{{-- Rendered as the « Transferts » tab of the Gestion de la caisse page
     (backoffice/caisses/index.blade.php) — the page owns the header. --}}
<div>
    @error('validate')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

    <x-backoffice.ui.card :title="__('Cash Transfers')">
        <x-slot:tools>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-backoffice.forms.select2 id="t-caisse-filter" model="caisseFilter" live inline
                    width="180px" :placeholder="__('All source cash registers')">
                    @foreach ($caisses as $caisse)
                        <option value="{{ $caisse->id }}">{{ $caisse->nom }}</option>
                    @endforeach
                </x-backoffice.forms.select2>
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
                @can('create', \App\Models\CaisseTransfer::class)
                    <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="create"
                        wire:loading.attr="disabled" wire:target="create">
                        <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="create" role="status" aria-hidden="true"></span>
                        <i class="ti ti-square-rounded-plus me-2" wire:loading.remove wire:target="create"></i>{{ __('Request Transfer') }}
                    </button>
                @endcan
            </div>
        </x-slot:tools>

        {{-- Status tabs: En attente / Validé / Annulé (CaisseTransfer::STATUTS) --}}
        <ul class="nav nav-tabs nav-tabs-solid mb-3" role="tablist">
            @foreach ([
                \App\Models\CaisseTransfer::STATUT_EN_ATTENTE => ['icon' => 'ti-clock', 'label' => \App\Models\CaisseTransfer::STATUT_EN_ATTENTE],
                \App\Models\CaisseTransfer::STATUT_VALIDE => ['icon' => 'ti-circle-check', 'label' => \App\Models\CaisseTransfer::STATUT_VALIDE],
                \App\Models\CaisseTransfer::STATUT_ANNULE => ['icon' => 'ti-ban', 'label' => \App\Models\CaisseTransfer::STATUT_ANNULE],
            ] as $statut => $tab)
                <li class="nav-item" role="presentation">
                    <a href="#" role="tab" wire:click.prevent="setStatutTab('{{ $statut }}')"
                        class="nav-link {{ $statutFilter === $statut ? 'active' : '' }}"
                        @if ($statutFilter === $statut) aria-current="page" @endif>
                        <i class="ti {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                        <span class="badge {{ $statutFilter === $statut ? 'bg-white text-dark' : 'badge-soft-secondary' }} ms-1">
                            {{ $statutCounts[$statut] ?? 0 }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($transfers->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No transfers with this status')" icon="ti ti-arrows-exchange" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Source cash register') }}</th>
                        <th>{{ __('Destination cash register') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Requested by') }}</th>
                        <th>{{ __('Validated by') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($transfers as $transfer)
                    @php $isPending = $transfer->statut === \App\Models\CaisseTransfer::STATUT_EN_ATTENTE; @endphp
                    <tr wire:key="trf-{{ $transfer->id }}">
                        <td class="fw-medium">{{ $transfer->reference }}</td>
                        <td>{{ $transfer->caisseSource?->nom ?? '—' }}</td>
                        <td>{{ $transfer->caisseDestination?->nom ?? '—' }}</td>
                        <td class="text-end fw-medium">{{ number_format((float) $transfer->montant, 2) }} DH</td>
                        <td>{{ $transfer->date_transfert?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $transfer->requestedBy?->nomComplet() ?? '—' }}</td>
                        <td>{{ $transfer->validatedBy?->nomComplet() ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $transfer->statut === \App\Models\CaisseTransfer::STATUT_VALIDE ? 'badge-soft-success' : ($transfer->statut === \App\Models\CaisseTransfer::STATUT_ANNULE ? 'badge-soft-secondary' : 'badge-soft-warning') }}">
                                {{ $transfer->statut }}
                            </span>
                        </td>
                        <td class="text-end">
                            {{-- Never a delete: a transfer is an immutable audit record. --}}
                            <x-backoffice.ui.action-menu :view="route('backoffice.caisse-transfers.show', $transfer)">
                                @if ($isPending)
                                    {{-- Two-person control: the requester can never validate his own
                                         transfer. The Domain action refuses it server-side too. --}}
                                    @can('validate', $transfer)
                                        @if ($transfer->requested_by !== $currentEmployeeId)
                                            <x-backoffice.ui.action-menu.item icon="ti-circle-check"
                                                wire:click="validateTransfer({{ $transfer->id }})"
                                                wire:confirm="{{ __('Validate this transfer? The balances will move.') }}"
                                                wire:loading.attr="disabled" wire:target="validateTransfer({{ $transfer->id }})">
                                                {{ __('Validate') }}
                                            </x-backoffice.ui.action-menu.item>
                                        @endif
                                    @endcan
                                    @can('update', $transfer)
                                        <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $transfer->id }})"
                                            wire:loading.attr="disabled" wire:target="edit({{ $transfer->id }})">
                                            {{ __('Edit') }}
                                        </x-backoffice.ui.action-menu.item>
                                        <x-backoffice.ui.action-menu.item icon="ti-ban" danger
                                            wire:click="cancel({{ $transfer->id }})"
                                            wire:confirm="{{ __('Cancel this transfer?') }}">
                                            {{ __('Cancel transfer') }}
                                        </x-backoffice.ui.action-menu.item>
                                    @endcan
                                @endif
                            </x-backoffice.ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$transfers" />
        @endif
    </x-backoffice.ui.card>

    {{-- Request/Edit modal --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Transfer') : __('Request Transfer') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <x-backoffice.ui.alert variant="info" :dismissible="false">
                                {{ $editingId
                                    ? __('Only the note can be edited while the transfer is pending — the cash registers and the amount are frozen.')
                                    : __('The balances do not move now: a different employee must validate this transfer first.') }}
                            </x-backoffice.ui.alert>
                            <div class="row">
                                <div class="col-md-6">
                                    <x-backoffice.forms.select2 id="t-source" model="caisse_source_id"
                                        :label="__('Source cash register')" required :placeholder="__('Choose…')"
                                        :disabled="(bool) $editingId">
                                        @foreach ($caisses as $caisse)
                                            <option value="{{ $caisse->id }}">{{ $caisse->nom }} ({{ number_format((float) $caisse->solde, 2) }} DH)</option>
                                        @endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-6">
                                    <x-backoffice.forms.select2 id="t-dest" model="caisse_destination_id"
                                        :label="__('Destination cash register')" required :placeholder="__('Choose…')"
                                        :disabled="(bool) $editingId">
                                        @foreach ($caisses as $caisse)
                                            <option value="{{ $caisse->id }}">{{ $caisse->nom }} ({{ number_format((float) $caisse->solde, 2) }} DH)</option>
                                        @endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="t-montant">{{ __('Amount') }}<span class="text-danger ms-1">*</span></label>
                                        <div class="input-group">
                                            {{-- The amount defines the money that will move on validation:
                                                 settable only at request time. --}}
                                            <input type="number" step="0.01" min="0.01" id="t-montant" wire:model="montant"
                                                class="form-control @error('montant') is-invalid @enderror"
                                                placeholder="0" @disabled($editingId)>
                                            <span class="input-group-text">DH</span>
                                            @error('montant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="t-note">{{ __('Note') }}</label>
                                        <textarea id="t-note" wire:model="note" rows="2"
                                            class="form-control @error('note') is-invalid @enderror"></textarea>
                                        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                                <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                                <span wire:loading wire:target="save">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ __('Saving…') }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div x-cloak class="modal-backdrop fade show" :style="show ? 'display:block; z-index:1055;' : 'display:none;'"></div>
    </div>
</div>
