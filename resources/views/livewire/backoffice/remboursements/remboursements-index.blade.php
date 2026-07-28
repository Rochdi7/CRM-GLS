{{-- Rendered as the « Remboursements » tab of the Gestion des dépenses page
     (backoffice/depenses/index.blade.php) — the page owns the header. --}}
<div>
    <x-backoffice.ui.card :title="__('Refunds')">
        <x-slot:tools>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-backoffice.forms.select2 id="r-caisse-filter" model="caisseFilter" live inline
                    width="180px" :placeholder="__('All cash registers')">
                    @foreach ($caisses as $caisse)
                        <option value="{{ $caisse->id }}">{{ $caisse->nom }}</option>
                    @endforeach
                </x-backoffice.forms.select2>
                <input type="date" class="form-control" style="min-width: 150px;"
                    wire:model.live="dateFrom" title="{{ __('From') }}">
                <input type="date" class="form-control" style="min-width: 150px;"
                    wire:model.live="dateTo" title="{{ __('To') }}">
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
                @can('create', \App\Models\Remboursement::class)
                    <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="create"
                        wire:loading.attr="disabled" wire:target="create">
                        <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="create" role="status" aria-hidden="true"></span>
                        <i class="ti ti-square-rounded-plus me-2" wire:loading.remove wire:target="create"></i>{{ __('Add Refund') }}
                    </button>
                @endcan
            </div>
        </x-slot:tools>

        @if ($remboursements->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No refunds yet')"
                :message="__('Record your first refund to get started.')" icon="ti ti-cash-banknote" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Beneficiary') }}</th>
                        <th>{{ __('Cash Register') }}</th>
                        <th class="text-end">{{ __('Amount') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Reason') }}</th>
                        <th>{{ __('Agent') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($remboursements as $remboursement)
                    <tr wire:key="rmb-{{ $remboursement->id }}">
                        <td class="fw-medium">{{ $remboursement->reference }}</td>
                        <td>{{ $remboursement->beneficiaire?->nomComplet() ?? '—' }}</td>
                        <td>{{ $remboursement->caisse?->nom ?? '—' }}</td>
                        <td class="text-end fw-medium text-danger">
                            -{{ number_format((float) $remboursement->montant, 2) }} DH
                        </td>
                        <td>{{ $remboursement->date_remboursement?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $remboursement->motif ?? '—' }}</td>
                        <td>{{ $remboursement->agent?->nomComplet() ?? '—' }}</td>
                        <td class="text-end">
                            {{-- No view route and NEVER a delete: a refund is an immutable audit record. --}}
                            <x-backoffice.ui.action-menu>
                                @can('update', $remboursement)
                                    <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $remboursement->id }})"
                                        wire:loading.attr="disabled" wire:target="edit({{ $remboursement->id }})">
                                        {{ __('Edit') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                            </x-backoffice.ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$remboursements" />
        @endif
    </x-backoffice.ui.card>

    {{-- Add/Edit modal --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Refund') : __('Add Refund') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            @if ($editingId)
                                <x-backoffice.ui.alert variant="info" :dismissible="false">
                                    {{ __('The amount and the cash register can never be edited — the till balance already moved.') }}
                                </x-backoffice.ui.alert>
                            @endif
                            <div class="row">
                                <div class="col-md-6">
                                    <x-backoffice.forms.select2 id="r-student" model="beneficiaire_id"
                                        :label="__('Beneficiary')" required :placeholder="__('Choose…')"
                                        :disabled="(bool) $editingId">
                                        @foreach ($students as $student)
                                            <option value="{{ $student->id }}">{{ $student->nomComplet() }}</option>
                                        @endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-6">
                                    <x-backoffice.forms.select2 id="r-caisse" model="caisse_id"
                                        :label="__('Cash Register')" required :placeholder="__('Choose…')"
                                        :disabled="(bool) $editingId">
                                        @foreach ($caisses as $caisse)
                                            <option value="{{ $caisse->id }}">{{ $caisse->nom }}</option>
                                        @endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="r-montant">{{ __('Amount') }}<span class="text-danger ms-1">*</span></label>
                                        <div class="input-group">
                                            {{-- Amount is settable only at creation: the balance already moved (schema §14). --}}
                                            <input type="number" step="0.01" min="0.01" id="r-montant" wire:model="montant"
                                                class="form-control @error('montant') is-invalid @enderror"
                                                placeholder="0" @disabled($editingId)>
                                            <span class="input-group-text">DH</span>
                                            @error('montant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="r-date">{{ __('Refund date') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="date" id="r-date" wire:model="date_remboursement"
                                            class="form-control @error('date_remboursement') is-invalid @enderror">
                                        @error('date_remboursement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="r-motif">{{ __('Reason') }}</label>
                                        <input type="text" id="r-motif" wire:model="motif"
                                            class="form-control @error('motif') is-invalid @enderror"
                                            placeholder="{{ __('e.g. Annulation d\'inscription') }}">
                                        @error('motif')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="r-note">{{ __('Note') }}</label>
                                        <textarea id="r-note" wire:model="note" rows="2"
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
