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
        <x-backoffice.ui.filter-bar>
            <x-backoffice.forms.select2 id="e-caisse-filter" model="caisseFilter" live
                :label="__('Till')" width="160px" :placeholder="__('All tills')">
                @foreach ($caisses as $c)<option value="{{ $c->id }}">{{ $c->nom }}</option>@endforeach
            </x-backoffice.forms.select2>
            <x-backoffice.forms.select2 id="e-methode-filter" model="methodeFilter" live
                :label="__('Method')" width="150px" :placeholder="__('All methods')">
                @foreach ($methodes as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
            </x-backoffice.forms.select2>
            <x-backoffice.ui.filter-bar.date-field :label="__('From date')" model="dateFrom" />
            <x-backoffice.ui.filter-bar.date-field :label="__('To date')" model="dateTo" />
            <x-slot:search>
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
            </x-slot:search>
        </x-backoffice.ui.filter-bar>

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
            <x-backoffice.ui.pagination :paginator="$encaissements">
                <x-backoffice.ui.per-page-select />
            </x-backoffice.ui.pagination>
        @endif
    </x-backoffice.ui.card>

    {{-- Add/Edit modal --}}
    <div
        x-data="{ show: @entangle('showModal') }"
        x-effect="
            const modal = $el.querySelector('.modal');
            $dispatch(show ? 'gls-select2-modal-opened' : 'gls-select2-modal-closed', { modal });
        "
    >
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
                                    <div class="col-md-6" wire:key="enc-student">
                                        <x-backoffice.forms.select2 id="e-student" model="student_id" live
                                            :label="__('Student')" required search="always" :placeholder="__('Choose…')">
                                            @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->nomComplet() }} ({{ $st->reference }})</option>@endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    <div class="col-md-6" wire:key="enc-inscription-{{ $student_id ?? 0 }}">
                                        <x-backoffice.forms.select2 id="e-inscription" model="inscription_id" live
                                            :label="__('Registration')" required :placeholder="__('Choose…')">
                                            @foreach ($inscriptions as $ins)<option value="{{ $ins->id }}">{{ $ins->reference }} — {{ $ins->group?->nom ?? '—' }}</option>@endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    {{-- No till picker: a payment always hits the signed-in
                                         employee's own till (server-set in create()); no Solde
                                         box either — matches the requested modal layout. --}}
                                </div>

                                {{-- Step 2 — pay any subset of the registration's fees, each
                                     with its own amount / method / date --}}
                                <div class="border-top pt-3">
                                    <h6 class="mb-1">{{ __('Fees of this registration') }}</h6>
                                    <p class="text-muted fs-13 mb-3">{{ __('Enter an amount on every fee this payment settles — leave the others blank.') }}</p>

                                    @if ($inscription_id === null)
                                        <x-backoffice.ui.alert variant="info" :dismissible="false">
                                            {{ __('Select a student and a registration to see its fees.') }}
                                        </x-backoffice.ui.alert>
                                    @elseif (count($paymentLines) === 0)
                                        <x-backoffice.ui.alert variant="warning" :dismissible="false">
                                            {{ __('Every fee of this registration is already fully paid.') }}
                                        </x-backoffice.ui.alert>
                                    @else
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm align-middle text-center mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-start">{{ __('Fee') }}</th>
                                                        <th>{{ __('Due date') }}</th>
                                                        <th>{{ __('Amount') }}</th>
                                                        <th>{{ __('Remaining') }}</th>
                                                        <th style="width:130px;">{{ __('Payment amount') }}</th>
                                                        <th style="width:150px;">{{ __('Method') }}</th>
                                                        <th style="width:150px;">{{ __('Date') }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($fees as $fee)
                                                        @continue(! isset($paymentLines[$fee['id']]))
                                                        <tr wire:key="enc-pl-{{ $fee['id'] }}">
                                                            <td class="text-start">{{ $fee['nom'] }}</td>
                                                            <td>{{ $fee['date_echeance']?->format('d/m/Y') }}</td>
                                                            <td>{{ number_format($fee['du'], 2) }} MAD</td>
                                                            <td class="text-danger fw-semibold">{{ number_format($fee['reste'], 2) }} MAD</td>
                                                            <td>
                                                                <input type="number" step="0.01" min="0" max="{{ $fee['reste'] }}"
                                                                    wire:model="paymentLines.{{ $fee['id'] }}.montant"
                                                                    class="form-control form-control-sm @error("paymentLines.{$fee['id']}.montant") is-invalid @enderror"
                                                                    placeholder="0">
                                                                @error("paymentLines.{$fee['id']}.montant")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                            </td>
                                                            <td>
                                                                <select wire:model="paymentLines.{{ $fee['id'] }}.methode" class="form-select form-select-sm">
                                                                    @foreach ($methodes as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="date" wire:model="paymentLines.{{ $fee['id'] }}.date_paiement"
                                                                    class="form-control form-control-sm @error("paymentLines.{$fee['id']}.date_paiement") is-invalid @enderror">
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @error('paymentLines')<div class="text-danger fs-13 mt-2">{{ $message }}</div>@enderror
                                    @endif
                                </div>

                                {{-- Shared cheque details: applied to every row paid by
                                     Chèque in this submit (one physical cheque per row is
                                     not supported — use separate submits for that). --}}
                                @if ($this->anyLineIsCheque())
                                    <div class="row border-top pt-3 mt-3">
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

                                <div class="row border-top pt-3 mt-3">
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label" for="e-note">{{ __('Note') }}</label>
                                            <textarea id="e-note" rows="2" wire:model="note" class="form-control"></textarea>
                                        </div>
                                    </div>
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

                                <div class="border-top pt-3">
                                    <div class="row">
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
                            @endif
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
