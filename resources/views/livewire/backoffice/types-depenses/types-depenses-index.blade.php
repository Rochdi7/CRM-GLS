{{-- Rendered as the « Types de dépenses » tab of the Gestion des dépenses page
     (backoffice/depenses/index.blade.php) — the page owns the header. --}}
<div>
    <x-backoffice.ui.card :title="__('Expense types')">
        <x-backoffice.ui.filter-bar>
            <x-slot:search>
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
            </x-slot:search>
            @can('create', \App\Models\TypeDepense::class)
                <x-slot:actions>
                    <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="create"
                        wire:loading.attr="disabled" wire:target="create">
                        <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="create" role="status" aria-hidden="true"></span>
                        <i class="ti ti-square-rounded-plus me-2" wire:loading.remove wire:target="create"></i>{{ __('Add Expense type') }}
                    </button>
                </x-slot:actions>
            @endcan
        </x-backoffice.ui.filter-bar>

        @error('delete')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

        @if ($types->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No expense types yet')" icon="ti ti-receipt-tax" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Expenses') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($types as $type)
                    <tr wire:key="td-{{ $type->id }}">
                        <td class="fw-medium">
                            {{ $type->nom }}
                            @if ($type->is_system)
                                {{-- Seeded + locked: payroll/transfers depend on this name --}}
                                <x-backoffice.ui.badge variant="secondary" class="ms-2">
                                    <i class="ti ti-lock me-1"></i>{{ __('System') }}
                                </x-backoffice.ui.badge>
                            @endif
                        </td>
                        <td><span class="badge badge-soft-secondary">{{ $type->depenses_count }}</span></td>
                        <td>
                            <x-backoffice.ui.badge :variant="$type->statut === \App\Models\TypeDepense::STATUT_ACTIF ? 'success' : 'secondary'">
                                {{ $type->statut === \App\Models\TypeDepense::STATUT_ACTIF ? __('Active') : __('Inactive') }}
                            </x-backoffice.ui.badge>
                        </td>
                        <td class="text-end">
                            {{-- System rows expose no edit/delete. The is_system check is
                                 explicit because super-admin bypasses every policy via
                                 Gate::before — the component guards it server-side too. --}}
                            <x-backoffice.ui.action-menu>
                                @unless ($type->is_system)
                                    @can('update', $type)
                                        <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $type->id }})"
                                            wire:loading.attr="disabled" wire:target="edit({{ $type->id }})">
                                            {{ __('Edit') }}
                                        </x-backoffice.ui.action-menu.item>
                                    @endcan
                                    @can('delete', $type)
                                        <x-backoffice.ui.action-menu.item icon="ti-trash" danger
                                            wire:click="delete({{ $type->id }})" wire:confirm="{{ __('Delete this expense type?') }}"
                                            wire:loading.attr="disabled" wire:target="delete({{ $type->id }})">
                                            {{ __('Delete') }}
                                        </x-backoffice.ui.action-menu.item>
                                    @endcan
                                @endunless
                            </x-backoffice.ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$types">
                <x-backoffice.ui.per-page-select />
            </x-backoffice.ui.pagination>
        @endif
    </x-backoffice.ui.card>

    {{-- Add/Edit modal (Alpine-driven) --}}
    <div
        x-data="{ show: @entangle('showModal') }"
        x-effect="
            const modal = $el.querySelector('.modal');
            $dispatch(show ? 'gls-select2-modal-opened' : 'gls-select2-modal-closed', { modal });
        "
    >
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Expense type') : __('Add Expense type') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="td-nom">{{ __('Name') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="text" id="td-nom" wire:model="nom"
                                            class="form-control @error('nom') is-invalid @enderror" placeholder="{{ __('e.g. Rent') }}">
                                        @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <x-backoffice.forms.select2 id="td-statut" model="statut"
                                        :label="__('Status')" required>
                                        <option value="{{ \App\Models\TypeDepense::STATUT_ACTIF }}">{{ __('Active') }}</option>
                                        <option value="{{ \App\Models\TypeDepense::STATUT_INACTIF }}">{{ __('Inactive') }}</option>
                                    </x-backoffice.forms.select2>
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
