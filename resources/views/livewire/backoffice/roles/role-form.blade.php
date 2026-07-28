<div>
    <x-backoffice.layout.page-header
        :title="$role ? __('Edit Role') : __('New Role')"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Roles & Permissions') => route('backoffice.roles.index'),
            ($role ? __('Edit Role') : __('New Role')) => null,
        ]" />

    <form wire:submit="save">
        <div class="row">
            <div class="col-lg-4">
                <x-backoffice.ui.card :title="__('Role')">
                    <div class="mb-3">
                        <label class="form-label" for="label">{{ __('Label') }}<span class="text-danger ms-1">*</span></label>
                        <input type="text" id="label" wire:model="label"
                            class="form-control @error('label') is-invalid @enderror"
                            placeholder="{{ __('e.g. Accountant') }}">
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="name">{{ __('Machine name') }}<span class="text-danger ms-1">*</span></label>
                        <input type="text" id="name" wire:model="name" @disabled($role !== null)
                            class="form-control @error('name') is-invalid @enderror"
                            placeholder="e.g. accountant">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">
                            {{ $role !== null
                                ? __('The machine name cannot be changed after creation.')
                                : __('Lowercase letters, numbers and dashes only.') }}
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                            <span wire:loading wire:target="save">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ __('Saving…') }}
                            </span>
                        </button>
                        <a href="{{ route('backoffice.roles.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                    </div>
                </x-backoffice.ui.card>
            </div>

            <div class="col-lg-8">
                <x-backoffice.ui.card :title="__('Permissions')">
                    <x-slot:tools>
                        <div class="input-icon-start position-relative">
                            <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                            <input type="text" class="form-control"
                                wire:model.live.debounce.400ms="permissionSearch"
                                placeholder="{{ __('Search permissions') }}">
                        </div>
                    </x-slot:tools>

                    @error('selected')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror
                    @error('selected.*')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

                    @forelse ($groups as $group => $permissions)
                        <div class="border rounded p-3 mb-3" wire:key="group-{{ $group }}">
                            <div class="d-flex align-items-center justify-content-between flex-wrap mb-2">
                                <h6 class="mb-0">{{ $group }}</h6>
                                <div>
                                    <button type="button" class="btn btn-link btn-sm p-0 me-2"
                                        wire:click="selectGroup('{{ $group }}')">{{ __('Select all') }}</button>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-muted"
                                        wire:click="clearGroup('{{ $group }}')">{{ __('Clear') }}</button>
                                </div>
                            </div>
                            <div class="row">
                                @foreach ($permissions as $permission => $permLabel)
                                    <div class="col-md-6" wire:key="perm-{{ $permission }}">
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox"
                                                id="perm-{{ $permission }}" value="{{ $permission }}"
                                                wire:model="selected">
                                            <label class="form-check-label" for="perm-{{ $permission }}">
                                                {{ $permLabel }}
                                                <span class="text-muted d-block fs-12"><code>{{ $permission }}</code></span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <x-backoffice.ui.empty-state :title="__('No permissions match your search')" icon="ti ti-key-off" />
                    @endforelse
                </x-backoffice.ui.card>
            </div>
        </div>
    </form>
</div>
