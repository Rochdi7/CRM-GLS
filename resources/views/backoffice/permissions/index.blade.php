<x-backoffice.layout.app :title="__('Permissions')">
    <x-backoffice.layout.page-header
        :title="__('Permissions')"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Roles & Permissions') => route('backoffice.roles.index'),
            __('Permissions') => null,
        ]" />

    <x-backoffice.ui.alert variant="info" :dismissible="false">
        {{ __('Permissions are defined by the application and cannot be created from the interface.') }}
        ({{ $seededCount }} {{ __('seeded') }})
    </x-backoffice.ui.alert>

    <div class="row">
        @foreach ($groups as $group => $permissions)
            <div class="col-lg-6">
                <x-backoffice.ui.card :title="$group">
                    <x-backoffice.ui.table :hover="false">
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Permission') }}</th>
                                <th>{{ __('Machine name') }}</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($permissions as $name => $label)
                            <tr>
                                <td>{{ $label }}</td>
                                <td><code>{{ $name }}</code></td>
                            </tr>
                        @endforeach
                    </x-backoffice.ui.table>
                </x-backoffice.ui.card>
            </div>
        @endforeach
    </div>
</x-backoffice.layout.app>
