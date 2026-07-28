<div>
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
        <h5 class="mb-0">{{ __('Rooms') }}</h5>
        @can('rooms.create')
            <x-backoffice.ui.button type="button" icon="ti ti-square-rounded-plus" wire:click="create" loading="create">
                {{ __('Add Room') }}
            </x-backoffice.ui.button>
        @endcan
    </div>

    @error('delete')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

    @if ($salles->isEmpty())
        <x-backoffice.ui.empty-state :title="__('No rooms yet')" icon="ti ti-door" />
    @else
        <x-backoffice.ui.table>
            <x-slot:head>
                <tr>
                    <th>{{ __('Room name') }}</th>
                    <th>{{ __('Center') }}</th>
                    <th>{{ __('Capacity') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Action') }}</th>
                </tr>
            </x-slot:head>
            @foreach ($salles as $s)
                <tr wire:key="salle-{{ $s->id }}">
                    <td class="fw-medium">{{ $s->nom }}</td>
                    <td>{{ $s->etablissement?->nom_centre ?? '—' }}</td>
                    <td>{{ $s->capacite ?? '—' }}</td>
                    <td>
                        <x-backoffice.ui.badge :variant="$s->statut === \App\Models\Salle::STATUT_ACTIVE ? 'success' : 'secondary'">
                            {{ $s->statut === \App\Models\Salle::STATUT_ACTIVE ? __('Active') : __('Inactive') }}
                        </x-backoffice.ui.badge>
                    </td>
                    <td class="text-end">
                        <x-backoffice.ui.action-menu>
                            @can('rooms.update')
                                <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $s->id }})"
                                    wire:loading.attr="disabled" wire:target="edit({{ $s->id }})">
                                    {{ __('Edit') }}
                                </x-backoffice.ui.action-menu.item>
                            @endcan
                            @can('rooms.delete')
                                <x-backoffice.ui.action-menu.item icon="ti-trash" danger
                                    wire:click="delete({{ $s->id }})" wire:confirm="{{ __('Delete this room?') }}"
                                    wire:loading.attr="disabled" wire:target="delete({{ $s->id }})">
                                    {{ __('Delete') }}
                                </x-backoffice.ui.action-menu.item>
                            @endcan
                        </x-backoffice.ui.action-menu>
                    </td>
                </tr>
            @endforeach
        </x-backoffice.ui.table>
        <x-backoffice.ui.pagination :paginator="$salles" />
    @endif

    {{-- Add/Edit modal (Alpine-driven) --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Room') : __('Add Room') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    @if ($etablissements->isEmpty())
                        <div class="modal-body">
                            <x-backoffice.ui.alert variant="warning" :dismissible="false">
                                {{ __('Create a center first before adding rooms.') }}
                            </x-backoffice.ui.alert>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">{{ __('Close') }}</button>
                        </div>
                    @else
                        <form wire:submit="save">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="s-nom">{{ __('Room name') }}<span class="text-danger ms-1">*</span></label>
                                            <input type="text" id="s-nom" wire:model="nom"
                                                class="form-control @error('nom') is-invalid @enderror" placeholder="ex : Salle 1">
                                            @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    {{-- Settings screen (admin-only): the center is always pickable
                                         here, unlike the regular CRUD modals — it just defaults to
                                         the active center. --}}
                                    <div class="col-md-6">
                                        <x-backoffice.forms.select2 id="s-etab" model="etablissement_id"
                                            :label="__('Center')" required :placeholder="__('Choose…')">
                                            @foreach ($etablissements as $etab)
                                                <option value="{{ $etab->id }}">{{ $etab->nom_centre }}</option>
                                            @endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="s-cap">{{ __('Capacity') }}</label>
                                            <input type="number" min="1" id="s-cap" wire:model="capacite"
                                                class="form-control @error('capacite') is-invalid @enderror" placeholder="ex : 20">
                                            @error('capacite')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <x-backoffice.forms.select2 id="s-statut" model="statut"
                                            :label="__('Status')" required>
                                            <option value="{{ \App\Models\Salle::STATUT_ACTIVE }}">{{ __('Active') }}</option>
                                            <option value="{{ \App\Models\Salle::STATUT_INACTIVE }}">{{ __('Inactive') }}</option>
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
                    @endif
                </div>
            </div>
        </div>
        <div x-cloak class="modal-backdrop fade show" :style="show ? 'display:block; z-index:1055;' : 'display:none;'"></div>
    </div>
</div>
