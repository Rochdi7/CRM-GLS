<div>
    <x-backoffice.layout.page-header
        :title="__('Students')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Students') => null]">
        @can('create', \App\Models\Student::class)
            <x-slot:actions>
                <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="create"
                    wire:loading.attr="disabled" wire:target="create">
                    <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="create" role="status" aria-hidden="true"></span>
                    <i class="ti ti-square-rounded-plus me-2" wire:loading.remove wire:target="create"></i>{{ __('Add Student') }}
                </button>
            </x-slot:actions>
        @endcan
    </x-backoffice.layout.page-header>

    @error('delete')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

    <x-backoffice.ui.card :title="__('Students')">
        <x-slot:tools>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-backoffice.forms.select2 id="s-niveau-filter" model="niveauFilter" live inline
                    width="150px" :placeholder="__('All levels')">
                    @foreach ($niveaux as $niv)
                        <option value="{{ $niv }}">{{ $niv }}</option>
                    @endforeach
                </x-backoffice.forms.select2>
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
            </div>
        </x-slot:tools>

        @if ($students->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No students yet')"
                :message="__('Add your first student to get started.')" icon="ti ti-school" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Level') }}</th>
                        <th>
                            <a href="#" wire:click.prevent="sortByAge" class="text-dark d-inline-flex align-items-center" role="button">
                                {{ __('Age') }}
                                <i class="ti {{ $ageSort === 'asc' ? 'ti-sort-ascending' : ($ageSort === 'desc' ? 'ti-sort-descending' : 'ti-arrows-sort') }} ms-1 text-muted"></i>
                            </a>
                        </th>
                        <th>{{ __('Center') }}</th>
                        <th>{{ __('Phone') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($students as $student)
                    <tr wire:key="stu-{{ $student->id }}">
                        <td><code>{{ $student->reference }}</code></td>
                        <td>
                            <a href="{{ route('backoffice.students.show', $student) }}" class="d-flex align-items-center text-dark">
                                <span class="avatar avatar-sm rounded-circle bg-primary-transparent me-2 d-inline-flex align-items-center justify-content-center overflow-hidden">
                                    @if ($url = $student->getFirstMediaUrl('photo'))
                                        <img src="{{ $url }}" alt="" class="w-100 h-100" style="object-fit:cover;">
                                    @else
                                        <span class="fw-bold text-primary">{{ strtoupper(mb_substr($student->prenom, 0, 1)) }}</span>
                                    @endif
                                </span>
                                <span class="fw-medium">{{ $student->nomComplet() }}</span>
                            </a>
                        </td>
                        <td>
                            @if ($student->niveau)
                                <span class="badge badge-soft-info">{{ $student->niveau }}</span>
                                {{-- Arbeit/Ausbildung → field, Studium → exam --}}
                                @if ($orientation = $student->orientation())
                                    <span class="badge badge-soft-secondary ms-1">{{ __($orientation) }}</span>
                                @endif
                            @else — @endif
                        </td>
                        <td>{{ $student->age() ?? '—' }}</td>
                        <td>{{ $student->etablissement?->nom_centre ?? '—' }}</td>
                        <td>{{ $student->telephone ?? '—' }}</td>
                        <td class="text-end">
                            <x-backoffice.ui.action-menu :view="route('backoffice.students.show', $student)">
                                @can('update', $student)
                                    <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $student->id }})"
                                        wire:loading.attr="disabled" wire:target="edit({{ $student->id }})">
                                        {{ __('Edit') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                                @can('delete', $student)
                                    <x-backoffice.ui.action-menu.item icon="ti-trash" danger
                                        wire:click="delete({{ $student->id }})" wire:confirm="{{ __('Delete this student?') }}"
                                        wire:loading.attr="disabled" wire:target="delete({{ $student->id }})">
                                        {{ __('Delete') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                            </x-backoffice.ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$students" />
        @endif
    </x-backoffice.ui.card>

    {{-- Add/Edit modal (Alpine-driven) --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Student') : __('Add Student') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            {{-- Identity header (always visible): photo + name + sexe + birth + level --}}
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
                                        <label class="form-label" for="s-nom">{{ __('Last Name') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="text" id="s-nom" wire:model="nom" class="form-control @error('nom') is-invalid @enderror" placeholder="ex : Rafik">
                                        @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="s-prenom">{{ __('First Name') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="text" id="s-prenom" wire:model="prenom" class="form-control @error('prenom') is-invalid @enderror" placeholder="ex : Mohammed">
                                        @error('prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label d-block">{{ __('Gender') }}</label>
                                        <div class="btn-group" role="group" aria-label="{{ __('Gender') }}">
                                            @foreach ($sexes as $sx)
                                                <input type="radio" class="btn-check" name="sexe" id="s-sexe-{{ $loop->index }}"
                                                    value="{{ $sx }}" wire:model="sexe" autocomplete="off">
                                                <label class="btn d-inline-flex align-items-center justify-content-center px-4 {{ $sx === 'Femme' ? 'gls-sexe-femme' : 'btn-outline-primary' }}" for="s-sexe-{{ $loop->index }}">
                                                    <i class="ti {{ $sx === 'Homme' ? 'ti-man' : 'ti-woman' }} me-1"></i>{{ $sx === 'Homme' ? __('Male') : __('Female') }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="s-naissance">{{ __('Date of birth') }}</label>
                                        <input type="date" id="s-naissance" wire:model="date_naissance" class="form-control @error('date_naissance') is-invalid @enderror">
                                        @error('date_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label" for="s-cin">{{ __('ID card number') }}</label>
                                        <input type="text" id="s-cin" wire:model="cin" class="form-control @error('cin') is-invalid @enderror" placeholder="ex : AB123456">
                                        @error('cin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                {{-- Level drives the orientation select next to it: Arbeit/Ausbildung
                                     ask for a professional field, Studium for an entrance exam.
                                     wire:model.live so the sibling appears without leaving the field. --}}
                                <div class="col-md-6">
                                    <x-backoffice.forms.select2 id="s-niveau" model="niveau" live
                                        :label="__('Level')" :placeholder="__('Choose…')">
                                        @foreach ($niveaux as $niv)<option value="{{ $niv }}">{{ $niv }}</option>@endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                @if (in_array($niveau, \App\Models\Student::NIVEAUX_AVEC_DOMAINE, true))
                                    <div class="col-md-6">
                                        <x-backoffice.forms.select2 id="s-domaine" model="domaine" required
                                            :label="__('Field')" :placeholder="__('Choose…')">
                                            @foreach ($domaines as $dom)<option value="{{ $dom }}">{{ __($dom) }}</option>@endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                @elseif ($niveau === \App\Models\Student::NIVEAU_STUDIUM)
                                    <div class="col-md-6">
                                        <x-backoffice.forms.select2 id="s-examen" model="examen_type" required
                                            :label="__('Entrance exam')" :placeholder="__('Choose…')">
                                            @foreach ($examenTypes as $ex)<option value="{{ $ex }}">{{ $ex }}</option>@endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                @endif
                            </div>

                            {{-- ================= TABS ================= --}}
                            <ul class="nav nav-tabs nav-tabs-solid mt-2 mb-3" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#stab-contact" role="tab"><i class="ti ti-mail me-1"></i>{{ __('Contact') }}</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#stab-parent" role="tab"><i class="ti ti-user me-1"></i>{{ __('Parent') }}</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#stab-autre" role="tab"><i class="ti ti-info-circle me-1"></i>{{ __('Other information') }}</a></li>
                            </ul>

                            <div class="tab-content">
                                {{-- ---- Contact ---- --}}
                                <div class="tab-pane fade show active" id="stab-contact" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <x-backoffice.forms.phone-country id="s-pays" />
                                        </div>
                                        <div class="col-md-4">
                                            <x-backoffice.forms.phone-input id="s-tel" :label="__('Phone')"
                                                model="telephone" :pays="$phonePays" />
                                        </div>
                                        <div class="col-md-4">
                                            <x-backoffice.forms.phone-input id="s-wa" :label="__('WhatsApp')"
                                                model="whatsapp" :pays="$phonePays" />
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="s-email">{{ __('Email') }}</label>
                                                <input type="email" id="s-email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="ex : nom@domaine.com">
                                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label class="form-label" for="s-adresse">{{ __('Address') }}</label>
                                                <input type="text" id="s-adresse" wire:model="adresse" class="form-control" placeholder="ex : 7 rue des fleurs">
                                            </div>
                                        </div>
                                        {{-- A specific center active in the top bar assigns the record
                                             automatically — the field only shows on « Tous les centres ». --}}
                                        @unless ($centerLocked)
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label" for="s-etab">{{ __('Center') }}</label>
                                                    <x-backoffice.forms.select2 id="s-etab" model="etablissement_id"
                                                        inline :placeholder="__('Choose…')">
                                                        @foreach ($etablissements as $etab)<option value="{{ $etab->id }}">{{ $etab->nom_centre }}</option>@endforeach
                                                    </x-backoffice.forms.select2>
                                                </div>
                                            </div>
                                        @endunless
                                    </div>
                                </div>

                                {{-- ---- Parent / guardian ---- --}}
                                <div class="tab-pane fade" id="stab-parent" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <x-backoffice.forms.select2 id="s-prelation" model="parent_relation"
                                                :label="__('Category')" :placeholder="__('Choose relative relation')">
                                                @foreach ($parentRelations as $rel)<option value="{{ $rel }}">{{ $rel }}</option>@endforeach
                                            </x-backoffice.forms.select2>
                                            @error('parent_relation')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="s-pnom">{{ __('Parent name') }}</label>
                                                <input type="text" id="s-pnom" wire:model="parent_nom" class="form-control" placeholder="ex : Alaoui">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label d-block">{{ __('Gender') }}</label>
                                                <div class="btn-group" role="group" aria-label="{{ __('Gender') }}">
                                                    @foreach ($sexes as $sx)
                                                        <input type="radio" class="btn-check" name="parent_sexe" id="s-psexe-{{ $loop->index }}"
                                                            value="{{ $sx }}" wire:model="parent_sexe" autocomplete="off">
                                                        <label class="btn d-inline-flex align-items-center justify-content-center px-4 {{ $sx === 'Femme' ? 'gls-sexe-femme' : 'btn-outline-primary' }}" for="s-psexe-{{ $loop->index }}">
                                                            <i class="ti {{ $sx === 'Homme' ? 'ti-man' : 'ti-woman' }} me-1"></i>{{ $sx === 'Homme' ? __('Male') : __('Female') }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @error('parent_sexe')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label" for="s-pcin">{{ __('CIN') }}</label>
                                                <input type="text" id="s-pcin" wire:model="parent_cin" class="form-control @error('parent_cin') is-invalid @enderror" placeholder="ex : AB123456">
                                                @error('parent_cin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <x-backoffice.forms.phone-input id="s-ptel" :label="__('Parent phone')"
                                                model="parent_telephone" :pays="$phonePays" />
                                        </div>
                                        <div class="col-md-4">
                                            <x-backoffice.forms.phone-input id="s-pwa" :label="__('Parent WhatsApp')"
                                                model="parent_whatsapp" :pays="$phonePays" />
                                        </div>
                                    </div>
                                </div>

                                {{-- ---- Autre informations ---- --}}
                                <div class="tab-pane fade" id="stab-autre" role="tabpanel">
                                    <div class="mb-2">
                                        <label class="form-label" for="s-note">{{ __('Note') }}</label>
                                        <textarea id="s-note" rows="3" wire:model="note" class="form-control" placeholder="{{ __('Additional notes about this student') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" wire:click="closeModal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save,photo">
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
