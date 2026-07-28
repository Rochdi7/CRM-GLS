<div>
    <x-backoffice.layout.page-header
        :title="__('User Authorization')"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Users') => route('backoffice.users.index'),
            $user->name => null,
        ]" />

    <form wire:submit="save">
        <div class="row">

            {{-- ============================ LEFT: user + roles ============================ --}}
            <div class="col-xl-4">

                {{-- User identity card --}}
                <x-backoffice.ui.card>
                    <div class="d-flex align-items-center mb-3">
                        <span class="avatar avatar-lg bg-primary-transparent rounded-circle me-3 d-flex align-items-center justify-content-center">
                            <span class="fs-20 fw-bold text-primary">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                        </span>
                        <div class="overflow-hidden">
                            <h5 class="mb-0 text-truncate">{{ $user->name }}</h5>
                            <p class="text-muted mb-0 text-truncate">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        @if ($user->employee)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">{{ __('Employee') }}</span>
                                <span class="fw-medium text-end">{{ $user->employee->nomComplet() }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">{{ __('Category') }}</span>
                                <span class="fw-medium">{{ $user->employee->categorie }}</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">{{ __('Center') }}</span>
                                <span class="fw-medium text-end">{{ $user->employee->etablissement?->nom_centre ?? __('No center (global)') }}</span>
                            </div>
                        @else
                            <p class="text-muted mb-0"><i class="ti ti-user-off me-1"></i>{{ __('No employee profile linked.') }}</p>
                        @endif
                    </div>
                </x-backoffice.ui.card>

                {{-- Roles as selectable cards --}}
                <x-backoffice.ui.card :title="__('Roles')">
                    <x-slot:tools>
                        <span class="badge badge-soft-primary">{{ count($selectedRoles) }} / {{ $roles->count() }}</span>
                    </x-slot:tools>

                    @error('roles')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror
                    @error('selectedRoles.*')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

                    <p class="text-muted fs-13 mb-3">{{ __('Assign one or more roles. Permissions are granted by the roles selected here.') }}</p>

                    <div class="d-flex flex-column gap-2">
                        @foreach ($roles as $role)
                            @php $checked = in_array($role->name, $selectedRoles, true); @endphp
                            <label wire:key="role-{{ $role->id }}"
                                class="d-flex align-items-center border rounded p-2 mb-0 cursor-pointer {{ $checked ? 'border-primary bg-primary-transparent' : '' }}"
                                for="role-{{ $role->name }}" style="cursor:pointer;">
                                <input class="form-check-input mt-0 me-2" type="checkbox" id="role-{{ $role->name }}"
                                    value="{{ $role->name }}" wire:model.live="selectedRoles">
                                <span class="flex-fill">
                                    <span class="fw-medium d-block">{{ $role->displayLabel() }}</span>
                                    <span class="text-muted fs-12">
                                        @if ($role->name === \App\Models\Role::SUPER_ADMIN)
                                            <i class="ti ti-shield-star me-1"></i>{{ __('All permissions') }}
                                        @else
                                            {{ trans_choice(':count permission|:count permissions', $role->permissions_count, ['count' => $role->permissions_count]) }}
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="d-flex align-items-center gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span class="spinner-border spinner-border-sm me-1" wire:loading wire:target="save" role="status" aria-hidden="true"></span>
                            <i class="ti ti-device-floppy me-1" wire:loading.remove wire:target="save"></i>
                            <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
                            <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
                        </button>
                        <a href="{{ route('backoffice.users.index') }}" class="btn btn-light">{{ __('Cancel') }}</a>
                    </div>
                </x-backoffice.ui.card>
            </div>

            {{-- ======================= RIGHT: effective + advanced ======================= --}}
            <div class="col-xl-8">

                {{-- Effective permissions summary --}}
                <x-backoffice.ui.card :title="__('Effective permissions')">
                    <x-slot:tools>
                        @unless ($isSuperAdmin)
                            <span class="badge badge-soft-success">{{ count($effective) }} / {{ $totalPermissions }}</span>
                        @endunless
                    </x-slot:tools>

                    @if ($isSuperAdmin)
                        <div class="alert alert-warning d-flex align-items-center mb-0" role="alert">
                            <i class="ti ti-shield-star fs-20 me-2"></i>
                            <div>
                                <strong>{{ __('Super administrator') }}</strong> —
                                {{ __('this user bypasses all permission checks.') }}
                            </div>
                        </div>
                    @elseif (count($effective) === 0)
                        <x-backoffice.ui.empty-state :title="__('No permissions yet')"
                            :message="__('Select a role on the left to grant permissions.')" icon="ti ti-lock" />
                    @else
                        <p class="text-muted fs-13 mb-3">
                            <span class="badge badge-soft-info me-1">{{ __('via role') }}</span>{{ __('granted by a role') }}
                            <span class="badge badge-soft-warning ms-3 me-1">{{ __('direct') }}</span>{{ __('assigned directly') }}
                        </p>

                        <div class="row g-3">
                            @foreach ($groups as $group => $permissions)
                                @php
                                    $held = array_values(array_filter(array_keys($permissions),
                                        fn ($p) => in_array($p, $viaRoles, true) || in_array($p, $direct, true)));
                                @endphp
                                @if ($held !== [])
                                    <div class="col-md-6">
                                        <div class="border rounded h-100 p-3">
                                            <h6 class="mb-2 d-flex align-items-center justify-content-between">
                                                {{ $group }}
                                                <span class="badge badge-soft-secondary">{{ count($held) }}/{{ count($permissions) }}</span>
                                            </h6>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach ($held as $permission)
                                                    <span class="badge {{ in_array($permission, $direct, true) ? 'badge-soft-warning' : 'badge-soft-info' }}">
                                                        {{ $permissions[$permission] }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </x-backoffice.ui.card>

                {{-- Advanced: direct permissions (collapsible, hidden by default) --}}
                @if ($canAssignDirect && ! $isSuperAdmin)
                    <div x-data="{ open: @js(count($direct) > 0) }" class="card">
                        <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                            <h4 class="mb-3">
                                <button type="button" class="btn btn-link p-0 text-dark text-decoration-none d-flex align-items-center"
                                    x-on:click="open = !open">
                                    <i class="ti ti-adjustments-cog me-2"></i>{{ __('Direct permissions (advanced)') }}
                                    <i class="ti ms-2" :class="open ? 'ti-chevron-up' : 'ti-chevron-down'"></i>
                                </button>
                            </h4>
                            @if (count($direct) > 0)
                                <div class="d-flex align-items-center flex-wrap mb-3">
                                    <span class="badge badge-soft-warning">{{ count($direct) }} {{ __('direct') }}</span>
                                </div>
                            @endif
                        </div>
                        <div class="card-body">
                            <div x-show="open" x-transition.duration.200ms>
                                <div class="alert alert-warning d-flex align-items-center" role="alert">
                                    <i class="ti ti-alert-triangle me-2"></i>
                                    <span class="fs-13">{{ __('Prefer roles. Direct permissions bypass the role model and are harder to audit.') }}</span>
                                </div>
                                @error('permissions')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

                                <div class="accordion" id="direct-perms-accordion">
                                    @foreach ($groups as $group => $permissions)
                                        @php
                                            $groupKeys = array_keys($permissions);
                                            $groupHeld = count(array_intersect($groupKeys, $direct));
                                            $slug = \Illuminate\Support\Str::slug($group);
                                        @endphp
                                        <div class="accordion-item border rounded mb-2" wire:key="dgroup-{{ $slug }}">
                                            <div class="d-flex align-items-center justify-content-between p-3">
                                                <button class="btn btn-link p-0 text-dark text-decoration-none fw-medium collapsed"
                                                    type="button" data-bs-toggle="collapse" data-bs-target="#dcol-{{ $slug }}">
                                                    <i class="ti ti-chevron-right me-1"></i>{{ $group }}
                                                    @if ($groupHeld > 0)
                                                        <span class="badge badge-soft-warning ms-2">{{ $groupHeld }}</span>
                                                    @endif
                                                </button>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-light"
                                                        wire:click="toggleGroup(@js($group), true)">{{ __('All') }}</button>
                                                    <button type="button" class="btn btn-outline-light"
                                                        wire:click="toggleGroup(@js($group), false)">{{ __('None') }}</button>
                                                </div>
                                            </div>
                                            <div id="dcol-{{ $slug }}" class="collapse" data-bs-parent="#direct-perms-accordion">
                                                <div class="px-3 pb-3">
                                                    <div class="row">
                                                        @foreach ($permissions as $permission => $permLabel)
                                                            <div class="col-md-6" wire:key="dperm-{{ $permission }}">
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        id="direct-{{ $permission }}" value="{{ $permission }}"
                                                                        wire:model.live="directPermissions">
                                                                    <label class="form-check-label" for="direct-{{ $permission }}">
                                                                        {{ $permLabel }}
                                                                        @if (in_array($permission, $viaRoles, true))
                                                                            <i class="ti ti-info-circle text-info ms-1"
                                                                                title="{{ __('Already granted by a role') }}"></i>
                                                                        @endif
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </form>
</div>
