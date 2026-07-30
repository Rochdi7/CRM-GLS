<div>
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
        <h5 class="mb-0">{{ __('Academic Years') }}</h5>
        @can('academic-years.create')
            <x-backoffice.ui.button type="button" icon="ti ti-square-rounded-plus" wire:click="create" loading="create">
                {{ __('Add Academic Year') }}
            </x-backoffice.ui.button>
        @endcan
    </div>

    @error('delete')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

    @if ($annees->isEmpty())
        <x-backoffice.ui.empty-state :title="__('No academic years yet')" icon="ti ti-calendar" />
    @else
        <x-backoffice.ui.table>
            <x-slot:head>
                <tr>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Start date') }}</th>
                    <th>{{ __('End date') }}</th>
                    <th>{{ __('Default year') }}</th>
                    <th>{{ __('Registrations') }}</th>
                    <th class="text-end">{{ __('Action') }}</th>
                </tr>
            </x-slot:head>
            @foreach ($annees as $a)
                <tr wire:key="annee-{{ $a->id }}">
                    <td class="fw-medium">{{ $a->nom }}</td>
                    <td>{{ $a->date_debut->format('d/m/Y') }}</td>
                    <td>{{ $a->date_fin->format('d/m/Y') }}</td>
                    <td>
                        @if ($a->par_defaut)
                            <x-backoffice.ui.badge variant="success">{{ __('Default') }}</x-backoffice.ui.badge>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        <x-backoffice.ui.badge :variant="$a->inscription_ouverte ? 'info' : 'secondary'">
                            {{ $a->inscription_ouverte ? __('Open') : __('Closed') }}
                        </x-backoffice.ui.badge>
                    </td>
                    <td class="text-end">
                        <x-backoffice.ui.action-menu>
                            @can('academic-years.update')
                                <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $a->id }})"
                                    wire:loading.attr="disabled" wire:target="edit({{ $a->id }})">
                                    {{ __('Edit') }}
                                </x-backoffice.ui.action-menu.item>
                            @endcan
                            @can('academic-years.delete')
                                <x-backoffice.ui.action-menu.item icon="ti-trash" danger
                                    wire:click="delete({{ $a->id }})" wire:confirm="{{ __('Delete this academic year?') }}"
                                    wire:loading.attr="disabled" wire:target="delete({{ $a->id }})">
                                    {{ __('Delete') }}
                                </x-backoffice.ui.action-menu.item>
                            @endcan
                        </x-backoffice.ui.action-menu>
                    </td>
                </tr>
            @endforeach
        </x-backoffice.ui.table>
        <x-backoffice.ui.pagination :paginator="$annees" />
    @endif

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
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Academic Year') : __('Add Academic Year') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="a-nom">{{ __('Name') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="text" id="a-nom" wire:model="nom" placeholder="2025/2026"
                                            class="form-control @error('nom') is-invalid @enderror">
                                        @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="a-debut">{{ __('Start date') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="date" id="a-debut" wire:model="date_debut"
                                            class="form-control @error('date_debut') is-invalid @enderror">
                                        @error('date_debut')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="a-fin">{{ __('End date') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="date" id="a-fin" wire:model="date_fin"
                                            class="form-control @error('date_fin') is-invalid @enderror">
                                        @error('date_fin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="a-defaut" wire:model="par_defaut">
                                        <label class="form-check-label" for="a-defaut">{{ __('Default year') }}</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="a-ouverte" wire:model="inscription_ouverte">
                                        <label class="form-check-label" for="a-ouverte">{{ __('Registrations open') }}</label>
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
