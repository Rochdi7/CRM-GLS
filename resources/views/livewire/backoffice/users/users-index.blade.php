<div>
    <x-backoffice.layout.page-header
        :title="__('Users')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Users') => null]" />

    <x-backoffice.ui.card :title="__('Users')">
        <x-slot:tools>
            <div class="input-icon-start position-relative">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
            </div>
        </x-slot:tools>

        @if ($users->count() === 0)
            <x-backoffice.ui.empty-state :title="__('No users found')" icon="ti ti-user-off" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Username') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Center') }}</th>
                        <th>{{ __('Roles') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="fw-medium">{{ $user->name }}</td>
                        <td>{{ $user->username ? '@'.$user->username : '—' }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->employee?->etablissement?->nom_centre ?? '—' }}</td>
                        <td>
                            @forelse ($user->roles as $role)
                                <span class="badge badge-soft-info me-1">{{ $role->displayLabel() }}</span>
                            @empty
                                <span class="text-muted">{{ __('No role') }}</span>
                            @endforelse
                        </td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $user->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </td>
                        <td class="text-end">
                            <x-backoffice.ui.action-menu>
                                @can('users.assign-roles')
                                    <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $user->id }})"
                                        wire:loading.attr="disabled" wire:target="edit({{ $user->id }})">
                                        {{ __('Edit') }}
                                    </x-backoffice.ui.action-menu.item>
                                    <x-backoffice.ui.action-menu.item icon="ti-shield-cog"
                                        :href="route('backoffice.users.authorization.edit', $user)">
                                        {{ __('Manage authorization') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                            </x-backoffice.ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$users" />
        @endif
    </x-backoffice.ui.card>

    {{-- Edit modal (Alpine-driven) --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ __('Edit User') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="u-name">{{ __('Name') }}<span class="text-danger ms-1">*</span></label>
                                <input type="text" id="u-name" wire:model="name" class="form-control @error('name') is-invalid @enderror">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="u-email">{{ __('Email') }}<span class="text-danger ms-1">*</span></label>
                                <input type="email" id="u-email" wire:model="email" class="form-control @error('email') is-invalid @enderror">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="u-username">{{ __('Username') }}</label>
                                <input type="text" id="u-username" wire:model="username" class="form-control @error('username') is-invalid @enderror">
                                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="u-active" wire:model="is_active">
                                <label class="form-check-label" for="u-active">{{ __('Active account (can sign in)') }}</label>
                            </div>

                            {{-- Password regeneration --}}
                            <div class="border-top pt-3">
                                @if ($regeneratedPassword)
                                    <div class="alert alert-info d-flex justify-content-between align-items-center mb-0">
                                        <span>{{ __('New password') }}: <code class="fs-14">{{ $regeneratedPassword }}</code></span>
                                        <span class="badge badge-soft-warning">{{ __('One-time') }}</span>
                                    </div>
                                    <p class="text-muted fs-12 mt-2 mb-0">{{ __('Share it now — it will not be shown again. The user must change it on next login.') }}</p>
                                @else
                                    <button type="button" class="btn btn-outline-warning btn-sm" wire:click="regeneratePassword"
                                        wire:confirm="{{ __('Generate a new one-time password for this user?') }}"
                                        wire:loading.attr="disabled" wire:target="regeneratePassword">
                                        <span class="spinner-border spinner-border-sm me-1" wire:loading wire:target="regeneratePassword" role="status" aria-hidden="true"></span>
                                        <i class="ti ti-key me-1" wire:loading.remove wire:target="regeneratePassword"></i>{{ __('Regenerate password') }}
                                    </button>
                                @endif
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
