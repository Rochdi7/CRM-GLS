<div>
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
        <h5 class="mb-0">{{ __('Centers') }}</h5>
        @can('centers.create')
            <x-backoffice.ui.button type="button" icon="ti ti-square-rounded-plus" wire:click="create" loading="create">
                {{ __('Add Center') }}
            </x-backoffice.ui.button>
        @endcan
    </div>

    @error('delete')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

    @if ($etablissements->isEmpty())
        <x-backoffice.ui.empty-state :title="__('No centers yet')" icon="ti ti-building" />
    @else
        <x-backoffice.ui.table>
            <x-slot:head>
                <tr>
                    <th>{{ __('Center name') }}</th>
                    <th>{{ __('City') }}</th>
                    <th>{{ __('Phone') }}</th>
                    <th>{{ __('Rooms') }}</th>
                    <th>{{ __('Head office') }}</th>
                    <th class="text-end">{{ __('Action') }}</th>
                </tr>
            </x-slot:head>
            @foreach ($etablissements as $e)
                <tr wire:key="etab-{{ $e->id }}">
                    <td class="fw-medium">{{ $e->nom_centre }}</td>
                    <td>{{ $e->ville }}</td>
                    <td>{{ $e->telephone ?? '—' }}</td>
                    <td><x-backoffice.ui.badge variant="secondary">{{ $e->salles_count }}</x-backoffice.ui.badge></td>
                    <td>
                        @if ($e->siege_social)
                            <x-backoffice.ui.badge variant="success">{{ __('Yes') }}</x-backoffice.ui.badge>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <x-backoffice.ui.action-menu>
                            @can('centers.update')
                                <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $e->id }})"
                                    wire:loading.attr="disabled" wire:target="edit({{ $e->id }})">
                                    {{ __('Edit') }}
                                </x-backoffice.ui.action-menu.item>
                            @endcan
                            @can('centers.delete')
                                <x-backoffice.ui.action-menu.item icon="ti-trash" danger
                                    wire:click="delete({{ $e->id }})" wire:confirm="{{ __('Delete this center?') }}"
                                    wire:loading.attr="disabled" wire:target="delete({{ $e->id }})">
                                    {{ __('Delete') }}
                                </x-backoffice.ui.action-menu.item>
                            @endcan
                        </x-backoffice.ui.action-menu>
                    </td>
                </tr>
            @endforeach
        </x-backoffice.ui.table>
        <x-backoffice.ui.pagination :paginator="$etablissements" />
    @endif

    {{-- Add/Edit modal (Alpine-driven, syncs with Livewire) --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Center') : __('Add Center') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="e-nom">{{ __('Center name') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="text" id="e-nom" wire:model="nom_centre"
                                            class="form-control @error('nom_centre') is-invalid @enderror" placeholder="ex : GLS Rabat">
                                        @error('nom_centre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="e-ville">{{ __('City') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="text" id="e-ville" wire:model="ville"
                                            class="form-control @error('ville') is-invalid @enderror" placeholder="ex : Rabat">
                                        @error('ville')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <x-backoffice.forms.phone-country id="e-pays" />
                                </div>
                                <div class="col-md-6">
                                    <x-backoffice.forms.phone-input id="e-tel" :label="__('Phone')"
                                        model="telephone" :pays="$phonePays" />
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="e-email">{{ __('Email') }}</label>
                                        <input type="email" id="e-email" wire:model="email"
                                            class="form-control @error('email') is-invalid @enderror" placeholder="ex : contact@gls.ma">
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="e-siege" wire:model="siege_social">
                                        <label class="form-check-label" for="e-siege">{{ __('Head office') }}</label>
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
