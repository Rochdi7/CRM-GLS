<div>
    <x-backoffice.layout.page-header
        :title="__('Employees')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Employees') => null]">
        @can('employees.create')
            <x-slot:actions>
                <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="create"
                    wire:loading.attr="disabled" wire:target="create">
                    <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="create" role="status" aria-hidden="true"></span>
                    <i class="ti ti-square-rounded-plus me-2" wire:loading.remove wire:target="create"></i>{{ __('Add Employee') }}
                </button>
            </x-slot:actions>
        @endcan
    </x-backoffice.layout.page-header>

    @error('delete')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

    <x-backoffice.ui.card :title="__('Employees')">
        <x-slot:tools>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-backoffice.forms.select2 id="emp-cat-filter" model="categorieFilter" live inline
                    width="180px" :placeholder="__('All categories')">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </x-backoffice.forms.select2>
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
            </div>
        </x-slot:tools>

        @if ($employees->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No employees yet')" icon="ti ti-users" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Gender') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Center') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($employees as $e)
                    <tr wire:key="emp-{{ $e->id }}">
                        <td><code>{{ $e->reference }}</code></td>
                        <td>
                            <span class="d-inline-flex align-items-center">
                                <span class="avatar avatar-sm rounded-circle bg-primary-transparent me-2 d-inline-flex align-items-center justify-content-center overflow-hidden">
                                    @if ($url = $e->getFirstMediaUrl('photo', 'thumb'))
                                        <img src="{{ $url }}" alt="" class="w-100 h-100" style="object-fit:cover;" loading="lazy">
                                    @else
                                        <span class="fw-bold text-primary">{{ strtoupper(mb_substr($e->prenom, 0, 1)) }}</span>
                                    @endif
                                </span>
                                <span class="fw-medium">{{ $e->nomComplet() }}</span>
                            </span>
                        </td>
                        <td><x-backoffice.ui.sexe-icon :sexe="$e->sexe" /></td>
                        <td><span class="badge badge-soft-info">{{ $e->categorie }}</span></td>
                        <td>{{ $e->etablissement?->nom_centre ?? '—' }}</td>
                        <td>{{ $e->telephone ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $e->statut === \App\Models\Employee::STATUT_ACTIF ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $e->statut }}
                            </span>
                        </td>
                        <td class="text-end">
                            <x-backoffice.ui.action-menu>
                                @can('employees.update')
                                    <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $e->id }})"
                                        wire:loading.attr="disabled" wire:target="edit({{ $e->id }})">
                                        {{ __('Edit') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                                @can('employees.delete')
                                    <x-backoffice.ui.action-menu.item icon="ti-trash" danger
                                        wire:click="delete({{ $e->id }})" wire:confirm="{{ __('Delete this employee?') }}"
                                        wire:loading.attr="disabled" wire:target="delete({{ $e->id }})">
                                        {{ __('Delete') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                            </x-backoffice.ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$employees" />
        @endif
    </x-backoffice.ui.card>

    {{-- Add/Edit modal — Alpine-driven so open/close stays in sync with Livewire.
         x-show alone controls display (no static .show / inline display that would
         fight it); explicit z-index keeps it above the backdrop and the sidebar. --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" aria-modal="true" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            {{-- Compact dialog for the credentials confirmation, XL only for the form --}}
            <div class="modal-dialog modal-dialog-centered {{ $newPassword ? '' : 'modal-xl' }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            {{ $editingId && $editingId > 0 ? __('Edit Employee') : __('Add Employee') }}
                        </h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>

                    {{-- After creation: show the one-time credentials --}}
                    @if ($newPassword)
                        <div class="modal-body">
                            <div class="text-center mb-3">
                                <span class="avatar avatar-xl bg-success-transparent rounded-circle d-inline-flex align-items-center justify-content-center mb-2">
                                    <i class="ti ti-circle-check fs-24 text-success"></i>
                                </span>
                                <h5>{{ __('Employee created') }}</h5>
                                <p class="text-muted">{{ __('Share these login credentials with the employee. They will not be shown again.') }}</p>
                            </div>
                            <div class="alert alert-info">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-medium">{{ __('Username') }}</span>
                                    <code class="fs-14">{{ $newUsername }}</code>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="fw-medium">{{ __('Password') }}</span>
                                    <code class="fs-14">{{ $newPassword }}</code>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-primary" wire:click="closeModal">{{ __('Done') }}</button>
                        </div>
                    @else
                        <form wire:submit="save">
                            <div class="modal-body">
                                {{-- Photo header, same pattern as the Student modal --}}
                                <div class="d-flex align-items-center mb-4">
                                    <span class="avatar avatar-xl rounded-circle bg-light me-3 overflow-hidden d-inline-flex align-items-center justify-content-center">
                                        @if ($photo)
                                            <img src="{{ $photo->temporaryUrl() }}" alt="" class="w-100 h-100" style="object-fit:cover;">
                                        @elseif ($existingPhotoUrl)
                                            <img src="{{ $existingPhotoUrl }}" alt="" class="w-100 h-100" style="object-fit:cover;">
                                        @else
                                            <i class="ti ti-camera fs-24 text-muted"></i>
                                        @endif
                                    </span>
                                    <div>
                                        <label class="form-label mb-1">{{ __('Photo') }}</label>
                                        <input type="file" wire:model="photo" accept="image/*" class="form-control form-control-sm @error('photo') is-invalid @enderror">
                                        <div wire:loading wire:target="photo" class="form-text">{{ __('Uploading…') }}</div>
                                        @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="emp-nom">{{ __('Last Name') }}<span class="text-danger ms-1">*</span></label>
                                            <input type="text" id="emp-nom" wire:model="nom" class="form-control @error('nom') is-invalid @enderror" placeholder="ex : Rafik">
                                            @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="emp-prenom">{{ __('First Name') }}<span class="text-danger ms-1">*</span></label>
                                            <input type="text" id="emp-prenom" wire:model="prenom" class="form-control @error('prenom') is-invalid @enderror" placeholder="ex : Mohammed">
                                            @error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label d-block">{{ __('Gender') }}<span class="text-danger ms-1">*</span></label>
                                            <div class="btn-group" role="group" aria-label="{{ __('Gender') }}">
                                                <input type="radio" class="btn-check" name="sexe" id="emp-sexe-h" value="Homme" wire:model="sexe" autocomplete="off">
                                                <label class="btn btn-outline-primary d-inline-flex align-items-center justify-content-center px-4" for="emp-sexe-h">
                                                    <i class="ti ti-man me-1"></i>{{ __('Male') }}
                                                </label>
                                                <input type="radio" class="btn-check" name="sexe" id="emp-sexe-f" value="Femme" wire:model="sexe" autocomplete="off">
                                                <label class="btn gls-sexe-femme d-inline-flex align-items-center justify-content-center px-4" for="emp-sexe-f">
                                                    <i class="ti ti-woman me-1"></i>{{ __('Female') }}
                                                </label>
                                            </div>
                                            @error('sexe')<div class="text-danger fs-12 mt-1">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="emp-naissance">{{ __('Date of birth') }}</label>
                                            <input type="date" id="emp-naissance" wire:model="date_naissance"
                                                class="form-control @error('date_naissance') is-invalid @enderror">
                                            @error('date_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <x-backoffice.forms.select2 id="emp-cat" model="categorie"
                                            :label="__('Employee category')" required>
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat }}">{{ $cat }}</option>
                                            @endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    <div class="col-md-6">
                                        <x-backoffice.forms.select2 id="emp-statut" model="statut"
                                            :label="__('Status')" required>
                                            @foreach ($statuts as $s)
                                                <option value="{{ $s }}">{{ $s }}</option>
                                            @endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    <div class="col-md-6">
                                        <x-backoffice.forms.phone-country id="emp-pays" />
                                    </div>
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6">
                                        <x-backoffice.forms.phone-input id="emp-tel" :label="__('Phone')"
                                            model="telephone" :pays="$phonePays" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-backoffice.forms.phone-input id="emp-wa" :label="__('WhatsApp')"
                                            model="whatsapp" :pays="$phonePays" />
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="emp-email">{{ __('Email') }}</label>
                                            <input type="email" id="emp-email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="ex : nom@domaine.com">
                                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="emp-adresse">{{ __('Address') }}</label>
                                            <input type="text" id="emp-adresse" wire:model="adresse" class="form-control @error('adresse') is-invalid @enderror" placeholder="ex : 12 rue Al Massira, Marrakech">
                                            @error('adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    {{-- A specific center active in the top bar assigns the record
                                         automatically — the field only shows on « Tous les centres ». --}}
                                    @unless ($centerLocked)
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="emp-etab">{{ __('Center') }}</label>
                                                <x-backoffice.forms.select2 id="emp-etab" model="etablissement_id"
                                                    inline :placeholder="__('Choose…')">
                                                    @foreach ($etablissements as $etab)
                                                        <option value="{{ $etab->id }}">{{ $etab->nom_centre }}</option>
                                                    @endforeach
                                                </x-backoffice.forms.select2>
                                                @error('etablissement_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    @endunless
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="emp-embauche">{{ __('Hire date') }}</label>
                                            <input type="date" id="emp-embauche" wire:model="date_embauche"
                                                class="form-control @error('date_embauche') is-invalid @enderror">
                                            @error('date_embauche')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="emp-salaire">{{ __('Salary') }}</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" min="0" id="emp-salaire" wire:model="salaire"
                                                    class="form-control @error('salaire') is-invalid @enderror" placeholder="ex : 5000">
                                                <span class="input-group-text">MAD</span>
                                                @error('salaire')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label class="form-label" for="emp-note">{{ __('Note') }}</label>
                                            <textarea id="emp-note" rows="2" wire:model="note"
                                                class="form-control @error('note') is-invalid @enderror"></textarea>
                                            @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                </div>
                                @unless ($editingId)
                                    <p class="text-muted fs-13 mb-0"><i class="ti ti-info-circle me-1"></i>{{ __('A login account will be created automatically for this employee.') }}</p>
                                @endunless
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
