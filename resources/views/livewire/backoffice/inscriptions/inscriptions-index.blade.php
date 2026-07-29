<div>
    <x-backoffice.layout.page-header
        :title="__('Registrations')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Registrations') => null]">
        @can('create', \App\Models\Inscription::class)
            <x-slot:actions>
                <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="create"
                    wire:loading.attr="disabled" wire:target="create">
                    <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="create" role="status" aria-hidden="true"></span>
                    <i class="ti ti-square-rounded-plus me-2" wire:loading.remove wire:target="create"></i>{{ __('Add Registration') }}
                </button>
            </x-slot:actions>
        @endcan
    </x-backoffice.layout.page-header>

    @error('delete')<x-backoffice.ui.alert variant="danger">{{ $message }}</x-backoffice.ui.alert>@enderror

    <x-backoffice.ui.card :title="__('Registrations')">
        <x-slot:tools>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-backoffice.forms.select2 id="i-statut-filter" model="statutFilter" live inline
                    width="150px" :placeholder="__('All statuses')">
                    @foreach ($statuts as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                </x-backoffice.forms.select2>
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
            </div>
        </x-slot:tools>

        @if ($inscriptions->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No registrations yet')" icon="ti ti-clipboard-list" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Student') }}</th>
                        <th>{{ __('Group') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Fees') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($inscriptions as $insc)
                    <tr wire:key="ins-{{ $insc->id }}">
                        <td><code>{{ $insc->reference }}</code></td>
                        <td class="fw-medium">{{ $insc->student?->nomComplet() ?? '—' }}</td>
                        <td>{{ $insc->group?->nom ?? '—' }}</td>
                        <td>{{ $insc->date_inscription?->format('d/m/Y') }}</td>
                        <td>{{ $insc->montant_total !== null ? number_format((float) $insc->montant_total, 2).' MAD' : '—' }}</td>
                        <td><span class="badge badge-soft-secondary">{{ $insc->fees_count }}</span></td>
                        <td><span class="badge badge-soft-info">{{ $insc->statut }}</span></td>
                        <td class="text-end">
                            <x-backoffice.ui.action-menu :view="route('backoffice.inscriptions.show', $insc)">
                                @can('update', $insc)
                                    <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $insc->id }})"
                                        wire:loading.attr="disabled" wire:target="edit({{ $insc->id }})">
                                        {{ __('Edit') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                                @can('delete', $insc)
                                    <x-backoffice.ui.action-menu.item icon="ti-trash" danger
                                        wire:click="delete({{ $insc->id }})" wire:confirm="{{ __('Delete this registration?') }}"
                                        wire:loading.attr="disabled" wire:target="delete({{ $insc->id }})">
                                        {{ __('Delete') }}
                                    </x-backoffice.ui.action-menu.item>
                                @endcan
                            </x-backoffice.ui.action-menu>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$inscriptions" />
        @endif
    </x-backoffice.ui.card>

    {{-- Add/Edit modal --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Registration') : __('Add Registration') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            {{-- Header: "Inscription pour" + identity (always visible) --}}
                            <div class="row">
                                @unless ($editingId)
                                    {{-- wire:key on every conditional sibling: these columns swap when
                                         the mode changes and each holds a wire:ignore Select2 island —
                                         without keys the morph pairs old/new columns positionally and
                                         scrambles the widgets (empty dropdowns). --}}
                                    <div class="col-md-4" wire:key="ins-mode">
                                        <x-backoffice.forms.select2 id="i-mode" model="inscriptionMode" live
                                            :label="__('Register for')" required>
                                            <option value="new">{{ __('New student') }}</option>
                                            <option value="existing">{{ __('Existing student') }}</option>
                                        </x-backoffice.forms.select2>
                                    </div>

                                    @if ($inscriptionMode === 'existing')
                                        <div class="col-md-8" wire:key="ins-existing-student">
                                            <x-backoffice.forms.select2 id="i-student" model="student_id"
                                                :label="__('Student')" required search="always" :placeholder="__('Choose…')">
                                                @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->nomComplet() }} ({{ $st->reference }})</option>@endforeach
                                            </x-backoffice.forms.select2>
                                        </div>
                                    @else
                                        <div class="col-md-4" wire:key="ins-new-nom">
                                            <div class="mb-3">
                                                <label class="form-label" for="i-nom">{{ __('Last Name') }}<span class="text-danger ms-1">*</span></label>
                                                <input type="text" id="i-nom" wire:model="new_nom" class="form-control @error('new_nom') is-invalid @enderror" placeholder="ex : Rafik">
                                                @error('new_nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4" wire:key="ins-new-prenom">
                                            <div class="mb-3">
                                                <label class="form-label" for="i-prenom">{{ __('First Name') }}<span class="text-danger ms-1">*</span></label>
                                                <input type="text" id="i-prenom" wire:model="new_prenom" class="form-control @error('new_prenom') is-invalid @enderror" placeholder="ex : Mohammed">
                                                @error('new_prenom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4" wire:key="ins-new-sexe">
                                            <div class="mb-3">
                                                <label class="form-label d-block">{{ __('Gender') }}</label>
                                                <div class="btn-group" role="group" aria-label="{{ __('Gender') }}">
                                                    @foreach ($sexes as $sx)
                                                        <input type="radio" class="btn-check" name="new_sexe" id="i-sexe-{{ $loop->index }}"
                                                            value="{{ $sx }}" wire:model="new_sexe" autocomplete="off">
                                                        <label class="btn d-inline-flex align-items-center justify-content-center px-4 {{ $sx === 'Femme' ? 'gls-sexe-femme' : 'btn-outline-primary' }}" for="i-sexe-{{ $loop->index }}">
                                                            <i class="ti {{ $sx === 'Homme' ? 'ti-man' : 'ti-woman' }} me-1"></i>{{ $sx === 'Homme' ? __('Male') : __('Female') }}
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4" wire:key="ins-new-naiss">
                                            <div class="mb-3">
                                                <label class="form-label" for="i-naiss">{{ __('Date of birth') }}</label>
                                                <input type="date" id="i-naiss" wire:model="new_date_naissance" class="form-control @error('new_date_naissance') is-invalid @enderror">
                                                @error('new_date_naissance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        <div class="col-md-4" wire:key="ins-new-cin">
                                            <div class="mb-3">
                                                <label class="form-label" for="i-cin">{{ __('ID card number') }}</label>
                                                <input type="text" id="i-cin" wire:model="new_cin" class="form-control @error('new_cin') is-invalid @enderror" placeholder="ex : AB123456">
                                                @error('new_cin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                        {{-- Level drives the orientation select next to it: Arbeit/Ausbildung
                                             ask for a professional field, Studium for an entrance exam. --}}
                                        <div class="col-md-4" wire:key="ins-new-niveau">
                                            <x-backoffice.forms.select2 id="i-niv" model="new_niveau" live
                                                :label="__('Interested in')" :placeholder="__('Choose…')">
                                                @foreach ($niveaux as $niv)<option value="{{ $niv }}">{{ $niv }}</option>@endforeach
                                            </x-backoffice.forms.select2>
                                        </div>
                                        @if (in_array($new_niveau, \App\Models\Student::NIVEAUX_AVEC_DOMAINE, true))
                                            <div class="col-md-4" wire:key="ins-new-domaine">
                                                <x-backoffice.forms.select2 id="i-domaine" model="new_domaine" required
                                                    :label="__('Field')" :placeholder="__('Choose…')">
                                                    @foreach ($domaines as $dom)<option value="{{ $dom }}">{{ __($dom) }}</option>@endforeach
                                                </x-backoffice.forms.select2>
                                            </div>
                                        @elseif ($new_niveau === \App\Models\Student::NIVEAU_STUDIUM)
                                            <div class="col-md-4" wire:key="ins-new-examen">
                                                <x-backoffice.forms.select2 id="i-examen" model="new_examen_type" required
                                                    :label="__('Entrance exam')" :placeholder="__('Choose…')">
                                                    @foreach ($examenTypes as $ex)<option value="{{ $ex }}">{{ $ex }}</option>@endforeach
                                                </x-backoffice.forms.select2>
                                            </div>
                                        @endif
                                        <div class="col-md-4" wire:key="ins-new-date">
                                            <div class="mb-3">
                                                <label class="form-label" for="i-date">{{ __('Registration date') }}<span class="text-danger ms-1">*</span></label>
                                                <input type="date" id="i-date" wire:model="date_inscription" class="form-control @error('date_inscription') is-invalid @enderror">
                                                @error('date_inscription')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="col-md-8" wire:key="ins-edit-student">
                                        <x-backoffice.forms.select2 id="i-student" model="student_id"
                                            :label="__('Student')" required search="always" :placeholder="__('Choose…')">
                                            @foreach ($students as $st)<option value="{{ $st->id }}">{{ $st->nomComplet() }} ({{ $st->reference }})</option>@endforeach
                                        </x-backoffice.forms.select2>
                                    </div>
                                    <div class="col-md-4" wire:key="ins-edit-date">
                                        <div class="mb-3">
                                            <label class="form-label" for="i-date">{{ __('Registration date') }}<span class="text-danger ms-1">*</span></label>
                                            <input type="date" id="i-date" wire:model="date_inscription" class="form-control @error('date_inscription') is-invalid @enderror">
                                            @error('date_inscription')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </div>
                                    </div>
                                @endunless
                            </div>

                            {{-- ================= TABS ================= --}}
                            <ul class="nav nav-tabs nav-tabs-solid mt-2 mb-3" role="tablist">
                                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#itab-affectation" role="tab"><i class="ti ti-calendar-event me-1"></i>{{ __('Assignment') }}</a></li>
                                @if (! $editingId && $inscriptionMode === 'new')
                                    <li class="nav-item" wire:key="ins-nav-contact"><a class="nav-link" data-bs-toggle="tab" href="#itab-contact" role="tab"><i class="ti ti-mail me-1"></i>{{ __('Contact') }}</a></li>
                                    <li class="nav-item" wire:key="ins-nav-parent"><a class="nav-link" data-bs-toggle="tab" href="#itab-parent" role="tab"><i class="ti ti-user me-1"></i>{{ __('Parent') }}</a></li>
                                @endif
                                <li class="nav-item" wire:key="ins-nav-autre"><a class="nav-link" data-bs-toggle="tab" href="#itab-autre" role="tab"><i class="ti ti-info-circle me-1"></i>{{ __('Other information') }}</a></li>
                            </ul>

                            <div class="tab-content">
                                {{-- ---- Affectation: group + dates + status + fees ---- --}}
                                <div class="tab-pane fade show active" id="itab-affectation" role="tabpanel" wire:key="ins-tab-affectation">
                                    <div class="row">
                                        {{-- Keyed: the date columns below appear/disappear with the
                                             group — unkeyed sibling shifts corrupt the Select2 islands. --}}
                                        <div class="col-md-6" wire:key="ins-group">
                                            <x-backoffice.forms.select2 id="i-group" model="group_id" live
                                                :label="__('Group')" required search="always" :placeholder="__('Choose a training')">
                                                @foreach ($groups as $g)<option value="{{ $g->id }}">{{ $g->nom }} — {{ $g->niveau }}</option>@endforeach
                                            </x-backoffice.forms.select2>
                                        </div>
                                        {{-- Status is only editable after creation — new
                                             registrations always start as "Active". --}}
                                        @if ($editingId)
                                            <div class="col-md-6" wire:key="ins-statut">
                                                <x-backoffice.forms.select2 id="i-statut" model="statut"
                                                    :label="__('Status')" required>
                                                    @foreach ($statuts as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach
                                                </x-backoffice.forms.select2>
                                            </div>
                                        @endif
                                        @if ($group_id)
                                            <div class="col-md-6" wire:key="ins-date-debut">
                                                <div class="mb-3">
                                                    <label class="form-label" for="i-debut">{{ __('Start date') }}</label>
                                                    <input type="date" id="i-debut" wire:model="date_debut" class="form-control" readonly>
                                                    <div class="form-text">{{ __('From the group') }}</div>
                                                </div>
                                            </div>
                                            <div class="col-md-6" wire:key="ins-date-fin">
                                                <div class="mb-3">
                                                    <label class="form-label" for="i-fin">{{ __('End date') }}</label>
                                                    <input type="date" id="i-fin" wire:model="date_fin" class="form-control" readonly>
                                                    <div class="form-text">{{ __('From the group') }}</div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Frais disponibles --}}
                                    @unless ($editingId)
                                        <div class="border-top pt-3">
                                            <h6 class="mb-1">{{ __('Available fees') }}</h6>
                                            @if ($group_id === null)
                                                <p class="text-muted fs-13">{{ __('Select a group to see its available fees.') }}</p>
                                            @elseif (count($feeLines) === 0)
                                                <x-backoffice.ui.alert variant="warning" :dismissible="false">
                                                    {{ __('This group has no fees assigned. Assign fees on the group first.') }}
                                                </x-backoffice.ui.alert>
                                            @else
                                                @php $total = 0; @endphp
                                                <div class="table-responsive">
                                                    <table class="table table-sm align-middle mb-1">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th>{{ __('Fee') }}</th>
                                                                <th>{{ __('Initial') }} (DH)</th>
                                                                <th>{{ __('Discount') }}</th>
                                                                <th>{{ __('Note') }}</th>
                                                                <th class="text-end">{{ __('Amount') }}</th>
                                                                <th>{{ __('Due date') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($feeLines as $i => $line)
                                                                @php $final = $this->lineMontant($i); $total += $final; @endphp
                                                                <tr wire:key="fl-{{ $i }}">
                                                                    <td class="fw-medium">{{ $line['nom'] }}</td>
                                                                    <td style="width:110px;">
                                                                        <input type="number" step="0.01" min="0" wire:model.live.debounce.400ms="feeLines.{{ $i }}.montant_initial"
                                                                            class="form-control form-control-sm">
                                                                    </td>
                                                                    <td style="width:190px;">
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number" step="0.01" min="0" max="100"
                                                                                wire:model.live.debounce.400ms="feeLines.{{ $i }}.remise_pct"
                                                                                class="form-control" placeholder="%">
                                                                            <span class="input-group-text">%</span>
                                                                            <input type="number" step="0.01" min="0"
                                                                                wire:model.live.debounce.400ms="feeLines.{{ $i }}.remise_montant"
                                                                                class="form-control" placeholder="DH">
                                                                            <span class="input-group-text">DH</span>
                                                                        </div>
                                                                    </td>
                                                                    <td style="width:150px;">
                                                                        <input type="text" wire:model="feeLines.{{ $i }}.note" class="form-control form-control-sm">
                                                                    </td>
                                                                    <td class="text-end fw-semibold" style="width:110px;">{{ number_format($final, 2) }} DH</td>
                                                                    <td style="width:150px;">
                                                                        <input type="date" wire:model="feeLines.{{ $i }}.date_echeance" class="form-control form-control-sm">
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                        <tfoot>
                                                            <tr>
                                                                <td colspan="4" class="text-end fw-semibold">{{ __('Total to pay') }}</td>
                                                                <td class="text-end fw-bold">{{ number_format($total, 2) }} DH</td>
                                                                <td></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            @endif
                                        </div>
                                    @endunless
                                </div>

                                {{-- ---- Contact (new student only) ---- --}}
                                @if (! $editingId && $inscriptionMode === 'new')
                                    <div class="tab-pane fade" id="itab-contact" role="tabpanel" wire:key="ins-tab-contact">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <x-backoffice.forms.phone-country id="i-pays" />
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="i-email">{{ __('Email') }}</label>
                                                    <input type="email" id="i-email" wire:model="new_email" class="form-control @error('new_email') is-invalid @enderror" placeholder="ex : nom@domaine.com">
                                                    @error('new_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <x-backoffice.forms.phone-input id="i-tel" :label="__('Phone')"
                                                    model="new_telephone" :pays="$phonePays" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-backoffice.forms.phone-input id="i-wa" :label="__('WhatsApp')"
                                                    model="new_whatsapp" :pays="$phonePays" />
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="i-adr">{{ __('Address') }}</label>
                                                    <input type="text" id="i-adr" wire:model="new_adresse" class="form-control @error('new_adresse') is-invalid @enderror" placeholder="ex : 7 rue des fleurs">
                                                    @error('new_adresse')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- ---- Parent (new student only) ---- --}}
                                    {{-- Same field set as the Student modal's Parent tab --}}
                                    <div class="tab-pane fade" id="itab-parent" role="tabpanel" wire:key="ins-tab-parent">
                                        <div class="row">
                                            <div class="col-md-4" wire:key="ins-prelation">
                                                <x-backoffice.forms.select2 id="i-prelation" model="new_parent_relation"
                                                    :label="__('Category')" :placeholder="__('Choose relative relation')">
                                                    @foreach ($parentRelations as $rel)<option value="{{ $rel }}">{{ $rel }}</option>@endforeach
                                                </x-backoffice.forms.select2>
                                                @error('new_parent_relation')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label" for="i-pnom">{{ __('Parent name') }}</label>
                                                    <input type="text" id="i-pnom" wire:model="new_parent_nom" class="form-control" placeholder="ex : Alaoui">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label d-block">{{ __('Gender') }}</label>
                                                    <div class="btn-group" role="group" aria-label="{{ __('Gender') }}">
                                                        @foreach ($sexes as $sx)
                                                            <input type="radio" class="btn-check" name="new_parent_sexe" id="i-psexe-{{ $loop->index }}"
                                                                value="{{ $sx }}" wire:model="new_parent_sexe" autocomplete="off">
                                                            <label class="btn d-inline-flex align-items-center justify-content-center px-4 {{ $sx === 'Femme' ? 'gls-sexe-femme' : 'btn-outline-primary' }}" for="i-psexe-{{ $loop->index }}">
                                                                <i class="ti {{ $sx === 'Homme' ? 'ti-man' : 'ti-woman' }} me-1"></i>{{ $sx === 'Homme' ? __('Male') : __('Female') }}
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    @error('new_parent_sexe')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label class="form-label" for="i-pcin">{{ __('CIN') }}</label>
                                                    <input type="text" id="i-pcin" wire:model="new_parent_cin" class="form-control @error('new_parent_cin') is-invalid @enderror" placeholder="ex : AB123456">
                                                    @error('new_parent_cin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <x-backoffice.forms.phone-input id="i-ptel" :label="__('Parent phone')"
                                                    model="new_parent_telephone" :pays="$phonePays" />
                                            </div>
                                            <div class="col-md-4">
                                                <x-backoffice.forms.phone-input id="i-pwa" :label="__('Parent WhatsApp')"
                                                    model="new_parent_whatsapp" :pays="$phonePays" />
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- ---- Autre informations ---- --}}
                                <div class="tab-pane fade" id="itab-autre" role="tabpanel" wire:key="ins-tab-autre">
                                    <div class="mb-3">
                                        <label class="form-label" for="i-note">{{ __('Note') }}</label>
                                        <textarea id="i-note" rows="3" wire:model="note" class="form-control" placeholder="{{ __('Additional notes about this registration') }}"></textarea>
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
