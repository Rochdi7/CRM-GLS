<div>
    <x-backoffice.layout.page-header
        :title="__('Roles & Permissions')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Roles & Permissions') => null]">
        @can('roles.create')
            <x-slot:actions>
                <a href="{{ route('backoffice.roles.create') }}" class="btn btn-primary d-flex align-items-center">
                    <i class="ti ti-square-rounded-plus me-2"></i>{{ __('New Role') }}
                </a>
            </x-slot:actions>
        @endcan
    </x-backoffice.layout.page-header>

    @error('delete')
        <x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>
    @enderror

    <x-backoffice.ui.card :title="__('Roles')">
        <x-slot:tools>
            <div class="input-icon-start position-relative">
                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                <input type="text" class="form-control" wire:model.live.debounce.400ms="search"
                    placeholder="{{ __('Search') }}">
            </div>
        </x-slot:tools>

        @if ($roles->count() === 0)
            <x-backoffice.ui.empty-state :title="__('No roles found')" icon="ti ti-shield-off" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Label') }}</th>
                        <th>{{ __('Machine name') }}</th>
                        <th>{{ __('Permissions') }}</th>
                        <th>{{ __('Users') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($roles as $role)
                    <tr wire:key="role-{{ $role->id }}">
                        <td class="fw-medium">{{ $role->displayLabel() }}</td>
                        <td><code>{{ $role->name }}</code></td>
                        <td>
                            @if ($role->isProtected())
                                <x-backoffice.ui.badge variant="warning">{{ __('All permissions') }}</x-backoffice.ui.badge>
                            @else
                                <x-backoffice.ui.badge variant="info">{{ $role->permissions_count }}</x-backoffice.ui.badge>
                            @endif
                        </td>
                        <td><x-backoffice.ui.badge variant="secondary">{{ $role->users_count }}</x-backoffice.ui.badge></td>
                        <td class="text-end">
                            @if (! $role->isProtected())
                                <x-backoffice.ui.action-menu>
                                    @can('roles.update')
                                        <x-backoffice.ui.action-menu.item icon="ti-edit"
                                            :href="route('backoffice.roles.edit', $role)">
                                            {{ __('Edit') }}
                                        </x-backoffice.ui.action-menu.item>
                                    @endcan
                                    @can('roles.delete')
                                        <x-backoffice.ui.action-menu.item icon="ti-trash" danger
                                            wire:click="deleteRole({{ $role->id }})" wire:confirm="{{ __('Delete this role?') }}"
                                            wire:loading.attr="disabled" wire:target="deleteRole({{ $role->id }})">
                                            {{ __('Delete') }}
                                        </x-backoffice.ui.action-menu.item>
                                    @endcan
                                </x-backoffice.ui.action-menu>
                            @else
                                <x-backoffice.ui.badge variant="danger">{{ __('Protected') }}</x-backoffice.ui.badge>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$roles" />
        @endif
    </x-backoffice.ui.card>
</div>
