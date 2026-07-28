<div>
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
        <h5 class="mb-0">{{ __('Fee catalog') }}</h5>
        @can('create', \App\Models\Frais::class)
            <x-backoffice.ui.button type="button" icon="ti ti-square-rounded-plus" wire:click="create" loading="create">
                {{ __('Add Fee') }}
            </x-backoffice.ui.button>
        @endcan
    </div>

    @error('delete')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

    @if ($frais->isEmpty())
        <x-backoffice.ui.empty-state :title="__('No fees yet')"
            :message="__('Add fees to the catalog, then assign them to groups.')" icon="ti ti-receipt" />
    @else
        <x-backoffice.ui.table>
            <x-slot:head>
                <tr>
                    <th>{{ __('Fee name') }}</th>
                    <th>{{ __('Groups') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th class="text-end">{{ __('Action') }}</th>
                </tr>
            </x-slot:head>
            @foreach ($frais as $f)
                <tr wire:key="frais-{{ $f->id }}">
                    <td class="fw-medium">{{ $f->nom }}</td>
                    <td><x-backoffice.ui.badge variant="secondary">{{ $f->groups_count }}</x-backoffice.ui.badge></td>
                    <td>
                        <x-backoffice.ui.badge :variant="$f->statut === \App\Models\Frais::STATUT_ACTIF ? 'success' : 'secondary'">
                            {{ $f->statut === \App\Models\Frais::STATUT_ACTIF ? __('Active') : __('Inactive') }}
                        </x-backoffice.ui.badge>
                    </td>
                    <td class="text-end">
                        <x-backoffice.ui.action-menu>
                            @can('update', $f)
                                <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $f->id }})"
                                    wire:loading.attr="disabled" wire:target="edit({{ $f->id }})">
                                    {{ __('Edit') }}
                                </x-backoffice.ui.action-menu.item>
                            @endcan
                            @can('delete', $f)
                                <x-backoffice.ui.action-menu.item icon="ti-trash" danger
                                    wire:click="delete({{ $f->id }})" wire:confirm="{{ __('Delete this fee?') }}"
                                    wire:loading.attr="disabled" wire:target="delete({{ $f->id }})">
                                    {{ __('Delete') }}
                                </x-backoffice.ui.action-menu.item>
                            @endcan
                        </x-backoffice.ui.action-menu>
                    </td>
                </tr>
            @endforeach
        </x-backoffice.ui.table>
        <x-backoffice.ui.pagination :paginator="$frais" />
    @endif

    {{-- Add/Edit modal --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Fee') : __('Add Fee') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="f-nom">{{ __('Fee name') }}<span class="text-danger ms-1">*</span></label>
                                <input type="text" id="f-nom" wire:model="nom" class="form-control @error('nom') is-invalid @enderror"
                                    placeholder="{{ __('e.g. Frais de Juillet') }}">
                                @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <x-backoffice.forms.select2 id="f-statut" model="statut"
                                :label="__('Status')" required>
                                <option value="{{ \App\Models\Frais::STATUT_ACTIF }}">{{ __('Active') }}</option>
                                <option value="{{ \App\Models\Frais::STATUT_INACTIF }}">{{ __('Inactive') }}</option>
                            </x-backoffice.forms.select2>
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
