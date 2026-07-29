<div>
    <x-backoffice.layout.page-header
        :title="__('Payments')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Payments') => null]">
        @can('create', \App\Models\Encaissement::class)
            <x-slot:actions>
                <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="create"
                    wire:loading.attr="disabled" wire:target="create">
                    <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="create" role="status" aria-hidden="true"></span>
                    <i class="ti ti-square-rounded-plus me-2" wire:loading.remove wire:target="create"></i>{{ __('Record Payment') }}
                </button>
            </x-slot:actions>
        @endcan
    </x-backoffice.layout.page-header>

    <x-backoffice.ui.card :title="__('Payments')">
        <x-slot:tools>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-backoffice.forms.select2 id="e-caisse-filter" model="caisseFilter" live inline
                    width="160px" :placeholder="__('All tills')">
                    @foreach ($caisses as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                </x-backoffice.forms.select2>
                <x-backoffice.forms.select2 id="e-methode-filter" model="methodeFilter" live inline
                    width="150px" :placeholder="__('All methods')">
                    @foreach ($methodes as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                </x-backoffice.forms.select2>
                <input type="date" class="form-control" style="min-width: 150px;"
                    wire:model.live="dateFrom" title="{{ __('From date') }}">
                <input type="date" class="form-control" style="min-width: 150px;"
                    wire:model.live="dateTo" title="{{ __('To date') }}">
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
            </div>
        </x-slot:tools>

        @if ($encaissements->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No payments yet')" icon="ti ti-cash-banknote" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Student') }}</th>
                        <th>{{ __('Fee') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Method') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Till') }}</th>
                        <th>{{ __('Fee status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($encaissements as $enc)
                    @php
                        $fee = $enc->fee;
                        $feeStatut = $fee?->statut;
                    @endphp
                    <tr wire:key="enc-{{ $enc->id }}">
                        <td><code>{{ $enc->reference }}</code></td>
                        <td class="fw-medium">{{ $enc->student?->nomComplet() ?? '—' }}</td>
                        <td>{{ $fee?->nom ?? '—' }}</td>
                        <td class="fw-semibold text-success">{{ number_format((float) $enc->montant, 2) }} MAD</td>
                        <td><span class="badge badge-soft-info">{{ $enc->methode }}</span></td>
                        <td>{{ $enc->date_paiement?->format('d/m/Y') }}</td>
                        <td>{{ $enc->caisse?->nom ?? '—' }}</td>
                        <td>
                            @if ($feeStatut)
                                <x-backoffice.ui.badge :variant="$feeStatut === \App\Models\InscriptionFee::STATUT_PAYE ? 'success' : ($feeStatut === \App\Models\InscriptionFee::STATUT_PAYE_PARTIELLEMENT ? 'warning' : 'danger')">
                                    {{ $feeStatut }}
                                </x-backoffice.ui.badge>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            {{-- No delete action: a payment is never deleted (audit trail). --}}
                            <x-backoffice.ui.action-menu :view="route('backoffice.encaissements.show', $enc)">
                                @can('update', $enc)
                                    <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $enc->id }})"
                                        wire:loading.attr="disabled" wire:target="edit({{ $enc->id }})">
                                        {{ __('Edit') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                            </x-backoffice.ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$encaissements" />
        @endif
    </x-backoffice.ui.card>

    {{-- Add/Edit modal --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Payment') : __('Record Payment') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            @if ($editingId)
                                <x-backoffice.ui.alert variant="warning" :dismissible="false">
                                    <i class="ti ti-lock me-2"></i>{{ __('The amount and the till cannot be changed after a payment is recorded — use a refund plus a new payment instead.') }}
                                </x-backoffice.ui.alert>
                            @endif

                            {{-- Step 1 — who pays and for what --}}
                            @unless ($editingId)
                                <div class="row">
                                    {{-- wire:key on each Select2 island: the cascade columns are
                                         conditional siblings and each holds a wire:ignore island —
                                         without keys the morph pairs them positionally and scrambles
                                         the widgets. --}}
                                    <div class="col-md-3" wire:key="enc-student">
                                        <x-backoffice.forms.select2 id="e-student" model="student_id" live
                                            :label="__('Student')" required search="always" :placeholder="__('Choose…')">
                                            @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->nomComplet() }} ({{ $st->reference }})</option>@endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    <div class="col-md-3" wire:key="enc-inscription-{{ $student_id ?? 0 }}">
                                        <x-backoffice.forms.select2 id="e-inscription" model="inscription_id" live
                                            :label="__('Registration')" required :placeholder="__('Choose…')">
                                            @foreach ($inscriptions as $ins)<option value="{{ $ins->id }}">{{ $ins->reference }} — {{ $ins->group?->nom ?? '—' }}</option>@endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    <div class="col-md-3" wire:key="enc-caisse">
                                        {{-- live: the Solde box beside it follows the selection --}}
                                        <x-backoffice.forms.select2 id="e-caisse" model="caisse_id" live
                                            :label="__('Till')" required :placeholder="__('Choose…')">
                                            @foreach ($caisses as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    <div class="col-md-3" wire:key="enc-solde">
                                        {{-- Legacy-app « Solde » box: the selected till's balance --}}
                                        <div class="mb-3">
                                            <label class="form-label" for="e-solde">{{ __('Balance') }}</label>
                                            <div class="input-group">
                                                <input type="text" id="e-solde" class="form-control" readonly
                                                    value="{{ $soldeCaisse !== null ? number_format($soldeCaisse, 2) : '—' }}">
                                                <span class="input-group-text">DH</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Step 2 — the fee lines of the chosen registration --}}
                                <div class="border-top pt-3">
                                    <h6 class="mb-1">{{ __('Fees of this registration') }}</h6>
                                    <p class="text-muted fs-13 mb-3">{{ __('Pick the fee this payment settles — the amount defaults to what is still owed on it.') }}</p>

                                    @if ($inscription_id === null)
                                        <x-backoffice.ui.alert variant="info" :dismissible="false">
                                            {{ __('Select a student and a registration to see its fees.') }}
                                        </x-backoffice.ui.alert>
                                    @elseif (count($fees) === 0)
                                        <x-backoffice.ui.alert variant="warning" :dismissible="false">
                                            {{ __('This registration has no fee lines.') }}
                                        </x-backoffice.ui.alert>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" style="width: 8%;"></th>
                                                        <th class="text-center" style="width: 30%;">{{ __('Fee') }}</th>
                                                        <th class="text-center" style="width: 16%;">{{ __('Due') }}</th>
                                                        <th class="text-center" style="width: 16%;">{{ __('Already paid') }}</th>
                                                        <th class="text-center" style="width: 16%;">{{ __('Remaining') }}</th>
                                                        <th class="text-center" style="width: 14%;">{{ __('Status') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($fees as $fee)
                                                        <tr wire:key="enc-fee-{{ $fee['id'] }}"
                                                            class="{{ (int) $inscription_fee_id === $fee['id'] ? 'table-active' : '' }}">
                                                            <td>
                                                                <input class="form-check-input" type="radio"
                                                                    id="e-fee-{{ $fee['id'] }}"
                                                                    value="{{ $fee['id'] }}"
                                                                    wire:model.live="inscription_fee_id"
                                                                    @disabled($fee['reste'] <= 0)>
                                                            </td>
                                                            <td class="text-start">
                                                                <label class="form-label mb-0" for="e-fee-{{ $fee['id'] }}">{{ $fee['nom'] }}</label>
                                                            </td>
                                                            <td>{{ number_format($fee['du'], 2) }} MAD</td>
                                                            <td class="text-success">{{ number_format($fee['paye'], 2) }} MAD</td>
                                                            <td class="{{ $fee['reste'] > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                                                                {{ number_format($fee['reste'], 2) }} MAD
                                                            </td>
                                                            <td>
                                                                <x-backoffice.ui.badge :variant="$fee['statut'] === \App\Models\InscriptionFee::STATUT_PAYE ? 'success' : ($fee['statut'] === \App\Models\InscriptionFee::STATUT_PAYE_PARTIELLEMENT ? 'warning' : 'danger')">
                                                                    {{ $fee['statut'] }}
                                                                </x-backoffice.ui.badge>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @error('inscription_fee_id')<div class="text-danger fs-13 mt-2">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                            @else
                                {{-- Edit: the payment target is frozen, shown read-only. --}}
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">{{ __('Amount') }}</label>
                                            <input type="text" class="form-control" value="{{ number_format((float) $montant, 2) }} MAD" disabled>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Step 3 — payment details --}}
                            <div class="border-top pt-3">
                                <div class="row">
                                    @unless ($editingId)
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="e-montant">{{ __('Amount') }}<span class="text-danger ms-1">*</span></label>
                                                <div class="input-group">
                                                    <input type="number" step="0.01" min="0.01" max="{{ $reste > 0 ? $reste : '' }}" id="e-montant"
                                                        wire:model="montant" class="form-control @error('montant') is-invalid @enderror"
                                                        placeholder="0">
                                                    <span class="input-group-text">MAD</span>
                                                    @error('montant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                                @if ($reste > 0)
                                                    <small class="text-muted">{{ __('Remaining on this fee:') }} {{ number_format($reste, 2) }} MAD</small>
                                                @endif
                                            </div>
                                        </div>
                                    @endunless
                                    <div class="col-md-4" wire:key="enc-methode">
                                        <x-backoffice.forms.select2 id="e-methode" model="methode" live
                                            :label="__('Payment method')" required>
                                            @foreach ($methodes as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label" for="e-date">{{ __('Payment date') }}<span class="text-danger ms-1">*</span></label>
                                            <input type="date" id="e-date" wire:model="date_paiement"
                                                class="form-control @error('date_paiement') is-invalid @enderror">
                                            @error('date_paiement')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>

                                @if ($methode === \App\Models\Encaissement::METHODE_CHEQUE)
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="e-cheque">{{ __('Cheque number') }}<span class="text-danger ms-1">*</span></label>
                                                <input type="text" id="e-cheque" wire:model="numero_cheque"
                                                    class="form-control @error('numero_cheque') is-invalid @enderror">
                                                @error('numero_cheque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="e-banque">{{ __('Bank') }}<span class="text-danger ms-1">*</span></label>
                                                <input type="text" id="e-banque" wire:model="banque"
                                                    class="form-control @error('banque') is-invalid @enderror">
                                                @error('banque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="e-cheque-echeance">{{ __('Cheque due date') }}<span class="text-danger ms-1">*</span></label>
                                                <input type="date" id="e-cheque-echeance" wire:model="date_echeance_cheque"
                                                    class="form-control @error('date_echeance_cheque') is-invalid @enderror">
                                                @error('date_echeance_cheque')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label" for="e-note">{{ __('Note') }}</label>
                                            <textarea id="e-note" rows="2" wire:model="note" class="form-control"></textarea>
                                        </div>
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
