{{-- Rendered as the « Dépenses » tab of the Gestion des dépenses page
     (backoffice/depenses/index.blade.php) — the page owns the header. --}}
<div>
    <x-backoffice.ui.card :title="__('Expenses')">
        <x-slot:tools>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-backoffice.forms.select2 id="d-type-filter" model="typeFilter" live inline
                    width="180px" :placeholder="__('All expense types')">
                    @foreach ($typesDepenses as $type)
                        <option value="{{ $type->id }}">{{ $type->nom }}</option>
                    @endforeach
                </x-backoffice.forms.select2>
                <x-backoffice.forms.select2 id="d-caisse-filter" model="caisseFilter" live inline
                    width="170px" :placeholder="__('All cash registers')">
                    @foreach ($caisses as $caisse)
                        <option value="{{ $caisse->id }}">{{ $caisse->nom }}</option>
                    @endforeach
                </x-backoffice.forms.select2>
                <input type="date" class="form-control" style="min-width: 150px;"
                    wire:model.live="dateFrom" title="{{ __('From date') }}">
                <input type="date" class="form-control" style="min-width: 150px;"
                    wire:model.live="dateTo" title="{{ __('To date') }}">
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
                @can('create', \App\Models\Depense::class)
                    <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="create"
                        wire:loading.attr="disabled" wire:target="create">
                        <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="create" role="status" aria-hidden="true"></span>
                        <i class="ti ti-square-rounded-plus me-2" wire:loading.remove wire:target="create"></i>{{ __('Record Expense') }}
                    </button>
                @endcan
            </div>
        </x-slot:tools>

        {{-- Sum of every row matching the current filters (all pages) --}}
        <div class="mb-3">
            <span class="fs-16 fw-semibold">{{ __('Total amount') }} : {{ number_format($montantTotal, 2) }} DH</span>
        </div>

        @if ($depenses->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No expenses yet')"
                :message="__('Record your first expense to get started.')" icon="ti ti-cash-banknote-off" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Expense type') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Cash register') }}</th>
                        <th>{{ __('Group') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Receipts') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($depenses as $depense)
                    <tr wire:key="dep-{{ $depense->id }}">
                        <td><code>{{ $depense->reference }}</code></td>
                        <td><x-backoffice.ui.badge variant="info">{{ $depense->typeDepense?->nom ?? '—' }}</x-backoffice.ui.badge></td>
                        <td class="text-truncate" style="max-width: 220px;">{{ $depense->description ?: '—' }}</td>
                        <td>{{ $depense->caisse?->nom ?? '—' }}</td>
                        <td>{{ $depense->group?->nom ?? '—' }}</td>
                        <td>{{ $depense->date_depense?->format('d/m/Y') }}</td>
                        <td class="fw-medium text-danger">- {{ number_format((float) $depense->montant, 2) }} MAD</td>
                        <td>
                            @php ($nbJustificatifs = $depense->getMedia('justificatifs')->count())
                            @if ($nbJustificatifs > 0)
                                <x-backoffice.ui.badge variant="secondary">
                                    <i class="ti ti-paperclip me-1"></i>{{ $nbJustificatifs }}
                                </x-backoffice.ui.badge>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            {{-- No delete action: an expense is never deleted (audit trail). --}}
                            <x-backoffice.ui.action-menu :view="route('backoffice.depenses.show', $depense)">
                                @can('update', $depense)
                                    <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $depense->id }})"
                                        wire:loading.attr="disabled" wire:target="edit({{ $depense->id }})">
                                        {{ __('Edit') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                            </x-backoffice.ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$depenses" />
        @endif
    </x-backoffice.ui.card>

    {{-- Add/Edit modal (Alpine-driven) --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Expense') : __('Record Expense') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            @if ($editingId)
                                <x-backoffice.ui.alert variant="warning" :dismissible="false">
                                    <i class="ti ti-alert-triangle me-2"></i>
                                    {{ __('The amount and the cash register cannot be changed after an expense is recorded — the till balance has already moved. Use a compensating entry instead.') }}
                                </x-backoffice.ui.alert>
                            @endif

                            <div class="row">
                                <div class="col-md-4">
                                    <x-backoffice.forms.select2 id="d-type" model="type_depense_id"
                                        :label="__('Expense type')" required :placeholder="__('Choose…')">
                                        @foreach ($typesDepenses as $type)
                                            <option value="{{ $type->id }}">{{ $type->nom }}</option>
                                        @endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="d-date">{{ __('Expense date') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="date" id="d-date" wire:model="date_depense"
                                            class="form-control @error('date_depense') is-invalid @enderror">
                                        @error('date_depense')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="d-facture">{{ __('Invoice reference') }}</label>
                                        <input type="text" id="d-facture" wire:model="reference_facture"
                                            class="form-control @error('reference_facture') is-invalid @enderror"
                                            placeholder="ex : FA-2026-0154">
                                        @error('reference_facture')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label" for="d-montant">
                                            {{ __('Amount') }}@unless ($editingId)<span class="text-danger ms-1">*</span>@endunless
                                        </label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" id="d-montant" wire:model="montant"
                                                class="form-control @error('montant') is-invalid @enderror"
                                                placeholder="0.00" @readonly((bool) $editingId)>
                                            <span class="input-group-text">MAD</span>
                                            @error('montant')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <x-backoffice.forms.select2 id="d-methode" model="methode_paiement"
                                        :label="__('Payment method')" :required="! $editingId" :placeholder="__('Choose…')">
                                        @foreach ($methodes as $methode)
                                            <option value="{{ $methode }}">{{ $methode }}</option>
                                        @endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-3">
                                    @if ($editingId)
                                        {{-- Read-only after creation (see UpdateDepenseRequest). --}}
                                        <div class="mb-3">
                                            <label class="form-label" for="d-caisse-ro">{{ __('Cash register') }}</label>
                                            <input type="text" id="d-caisse-ro" class="form-control" readonly
                                                value="{{ $caisses->firstWhere('id', $caisse_id)?->nom ?? '—' }}">
                                        </div>
                                    @else
                                        {{-- live: the Solde box beside it follows the selection --}}
                                        <x-backoffice.forms.select2 id="d-caisse" model="caisse_id" live
                                            :label="__('Cash register')" required :placeholder="__('Choose…')">
                                            @foreach ($caisses as $caisse)
                                                <option value="{{ $caisse->id }}">{{ $caisse->nom }}</option>
                                            @endforeach
                                        </x-backoffice.forms.select2>
                                    @endif
                                </div>
                                <div class="col-md-3">
                                    {{-- Legacy-app « Solde » box: the selected till's balance --}}
                                    <div class="mb-3">
                                        <label class="form-label" for="d-solde">{{ __('Balance') }}</label>
                                        <div class="input-group">
                                            <input type="text" id="d-solde" class="form-control" readonly
                                                value="{{ $soldeCaisse !== null ? number_format($soldeCaisse, 2) : '—' }}">
                                            <span class="input-group-text">DH</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <x-backoffice.forms.select2 id="d-group" model="group_id"
                                        :label="__('Group')" :placeholder="__('Choose…')">
                                        @foreach ($groups as $group)
                                            <option value="{{ $group->id }}">{{ $group->nom }} — {{ $group->niveau }}</option>
                                        @endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="d-description">{{ __('Description') }}</label>
                                        <input type="text" id="d-description" wire:model="description"
                                            class="form-control @error('description') is-invalid @enderror"
                                            placeholder="{{ __('e.g. Office supplies purchase') }}">
                                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="d-mots-cles">{{ __('Keywords') }}</label>
                                        <input type="text" id="d-mots-cles" wire:model="mots_cles"
                                            class="form-control @error('mots_cles') is-invalid @enderror"
                                            placeholder="{{ __('Comma-separated keywords') }}">
                                        @error('mots_cles')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label" for="d-note">{{ __('Note') }}</label>
                                        <textarea id="d-note" rows="2" wire:model="note"
                                            class="form-control @error('note') is-invalid @enderror"></textarea>
                                        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Receipts (justificatifs) --}}
                            <div class="border-top pt-3">
                                <h6 class="mb-1">{{ __('Receipts') }}</h6>
                                <p class="text-muted fs-13 mb-3">{{ __('Attach the invoices or receipts backing this expense (JPG, PNG, WEBP or PDF, 5 MB max each).') }}</p>

                                <div class="mb-3">
                                    <input type="file" multiple wire:model="justificatifs"
                                        accept="image/jpeg,image/png,image/webp,application/pdf"
                                        class="form-control form-control-sm @error('justificatifs.*') is-invalid @enderror">
                                    <div wire:loading wire:target="justificatifs" class="form-text">{{ __('Uploading…') }}</div>
                                    @error('justificatifs.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>

                                @if (count($justificatifs) > 0)
                                    <ul class="list-group list-group-flush mb-3">
                                        @foreach ($justificatifs as $index => $file)
                                            <li class="list-group-item d-flex align-items-center px-0" wire:key="new-just-{{ $index }}">
                                                <i class="ti ti-file-upload me-2 text-primary"></i>
                                                <span class="text-truncate">{{ $file?->getClientOriginalName() }}</span>
                                                <span class="badge badge-soft-primary ms-auto">{{ __('Pending') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if (count($existingJustificatifs) > 0)
                                    <ul class="list-group list-group-flush">
                                        @foreach ($existingJustificatifs as $mediaId => $just)
                                            <li class="list-group-item d-flex align-items-center px-0" wire:key="just-{{ $mediaId }}">
                                                <i class="ti ti-paperclip me-2 text-muted"></i>
                                                <a href="{{ $just['url'] }}" target="_blank" rel="noopener" class="text-truncate">{{ $just['name'] }}</a>
                                                <button type="button" class="btn btn-sm btn-outline-danger ms-auto"
                                                    wire:click="removeJustificatif({{ $mediaId }})"
                                                    wire:loading.attr="disabled" wire:target="removeJustificatif({{ $mediaId }})">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,justificatifs">
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
