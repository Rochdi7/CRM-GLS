<div>
    <x-backoffice.layout.page-header
        :title="__('My Profile')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('My Profile') => null]" />

    <div class="row">
        {{-- Identity summary --}}
        <div class="col-xl-4">
            <x-backoffice.ui.card>
                <div class="text-center">
                    <span class="avatar avatar-xxl bg-primary-transparent rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
                        <span class="fs-24 fw-bold text-primary">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                    </span>
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-2">{{ $user->email }}</p>
                    @foreach ($user->roles as $role)
                        <span class="badge badge-soft-info">{{ $role->displayLabel() }}</span>
                    @endforeach
                </div>
                @if ($user->employee)
                    {{-- Read-only HR details — managed on the Employees page, not editable here. --}}
                    <div class="border-top mt-3 pt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Reference') }}</span>
                            <code>{{ $user->employee->reference }}</code>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Category') }}</span>
                            <span class="fw-medium">{{ $user->employee->categorie }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Gender') }}</span>
                            <span class="fw-medium"><x-backoffice.ui.sexe-icon :sexe="$user->employee->sexe" /></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Email') }}</span>
                            <span class="fw-medium text-end text-truncate ms-2">{{ $user->employee->email ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Date of birth') }}</span>
                            <span class="fw-medium">{{ $user->employee->date_naissance?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Hire date') }}</span>
                            <span class="fw-medium">{{ $user->employee->date_embauche?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Salary') }}</span>
                            <span class="fw-medium">{{ $user->employee->salaire !== null ? number_format((float) $user->employee->salaire, 2).' MAD' : '—' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{{ __('Center') }}</span>
                            <span class="fw-medium text-end">{{ $user->employee->etablissement?->nom_centre ?? __('No center (global)') }}</span>
                        </div>
                    </div>
                @endif
            </x-backoffice.ui.card>
        </div>

        <div class="col-xl-8">
            {{-- Profile info --}}
            <x-backoffice.ui.card :title="__('Profile information')">
                <form wire:submit="updateProfile">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="p-name">{{ __('Name') }}<span class="text-danger ms-1">*</span></label>
                                <input type="text" id="p-name" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="ex : Mohammed Rafik">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="p-email">{{ __('Email') }}<span class="text-danger ms-1">*</span></label>
                                <input type="email" id="p-email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="ex : nom@domaine.com">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        @if ($user->employee)
                            <div class="col-md-6">
                                <x-backoffice.forms.phone-country id="p-pays" />
                            </div>
                            <div class="col-md-6">
                                <x-backoffice.forms.phone-input id="p-tel" :label="__('Phone')"
                                    model="telephone" :pays="$phonePays" />
                            </div>
                            <div class="col-md-6">
                                <x-backoffice.forms.phone-input id="p-wa" :label="__('WhatsApp')"
                                    model="whatsapp" :pays="$phonePays" />
                            </div>
                        @endif
                    </div>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="updateProfile">
                        <span wire:loading.remove wire:target="updateProfile">{{ __('Save') }}</span>
                        <span wire:loading wire:target="updateProfile">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ __('Saving…') }}
                        </span>
                    </button>
                </form>
            </x-backoffice.ui.card>

            {{-- Change password --}}
            <x-backoffice.ui.card :title="__('Change password')">
                <form wire:submit="updatePassword">
                    <div class="mb-3">
                        <label class="form-label" for="p-cur">{{ __('Current Password') }}<span class="text-danger ms-1">*</span></label>
                        <input type="password" id="p-cur" wire:model="current_password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="p-new">{{ __('New Password') }}<span class="text-danger ms-1">*</span></label>
                                <input type="password" id="p-new" wire:model="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label" for="p-conf">{{ __('Confirm Password') }}<span class="text-danger ms-1">*</span></label>
                                <input type="password" id="p-conf" wire:model="password_confirmation" class="form-control" autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="updatePassword">
                        <span wire:loading.remove wire:target="updatePassword">{{ __('Change password') }}</span>
                        <span wire:loading wire:target="updatePassword">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>{{ __('Saving…') }}
                        </span>
                    </button>
                </form>
            </x-backoffice.ui.card>
        </div>
    </div>
</div>
