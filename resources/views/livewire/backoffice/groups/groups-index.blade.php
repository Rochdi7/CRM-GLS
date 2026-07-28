<div>
    <x-backoffice.layout.page-header
        :title="__('Groups')"
        :breadcrumbs="[__('Dashboard') => route('backoffice.dashboard'), __('Groups') => null]">
        @can('create', \App\Models\Group::class)
            <x-slot:actions>
                <button type="button" class="btn btn-primary d-flex align-items-center" wire:click="create"
                    wire:loading.attr="disabled" wire:target="create">
                    <span class="spinner-border spinner-border-sm me-2" wire:loading wire:target="create" role="status" aria-hidden="true"></span>
                    <i class="ti ti-square-rounded-plus me-2" wire:loading.remove wire:target="create"></i>{{ __('Add Group') }}
                </button>
            </x-slot:actions>
        @endcan
    </x-backoffice.layout.page-header>

    <x-backoffice.ui.card :title="__('Groups')">
        <x-slot:tools>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
            </div>
        </x-slot:tools>

        {{-- Status tabs: En formation / Pré-inscription / Historique (groups ended) --}}
        <ul class="nav nav-tabs nav-tabs-solid mb-3" role="tablist">
            @foreach ([
                \App\Models\Group::STATUT_EN_FORMATION => ['icon' => 'ti-school', 'label' => \App\Models\Group::STATUT_EN_FORMATION],
                \App\Models\Group::STATUT_PRE_INSCRIPTION => ['icon' => 'ti-folder', 'label' => \App\Models\Group::STATUT_PRE_INSCRIPTION],
                \App\Models\Group::STATUT_FIN_FORMATION => ['icon' => 'ti-history', 'label' => __('History')],
            ] as $statut => $tab)
                <li class="nav-item" role="presentation">
                    <a href="#" role="tab" wire:click.prevent="setStatutTab('{{ $statut }}')"
                        class="nav-link {{ $statutFilter === $statut ? 'active' : '' }}"
                        @if ($statutFilter === $statut) aria-current="page" @endif>
                        <i class="ti {{ $tab['icon'] }} me-1"></i>{{ $tab['label'] }}
                        <span class="badge {{ $statutFilter === $statut ? 'bg-white text-dark' : 'badge-soft-secondary' }} ms-1">
                            {{ $statutCounts[$statut] ?? 0 }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($groups->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No groups with this status')" icon="ti ti-users-group" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Level') }}</th>
                        <th>{{ __('Teacher') }}</th>
                        <th>{{ __('Students') }}</th>
                        <th>{{ __('Fees') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($groups as $group)
                    <tr wire:key="grp-{{ $group->id }}">
                        <td class="fw-medium">{{ $group->nom }}</td>
                        <td><span class="badge badge-soft-info">{{ $group->niveau }}</span></td>
                        <td>{{ $group->enseignant?->nomComplet() ?? '—' }}</td>
                        <td><span class="badge badge-soft-secondary">{{ $group->inscriptions_count }}</span></td>
                        <td><span class="badge badge-soft-secondary">{{ $group->frais_count }}</span></td>
                        <td>
                            <span class="badge {{ $group->statut === \App\Models\Group::STATUT_EN_FORMATION ? 'badge-soft-success' : ($group->statut === \App\Models\Group::STATUT_FIN_FORMATION ? 'badge-soft-secondary' : 'badge-soft-warning') }}">
                                {{ $group->statut }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end">
                                @if ($group->statut === \App\Models\Group::STATUT_FIN_FORMATION)
                                    <span class="badge badge-soft-secondary me-2">{{ __('Archived') }}</span>
                                @endif
                                <x-backoffice.ui.action-menu :view="route('backoffice.groups.show', $group)">
                                    @can('update', $group)
                                        @if ($group->statut !== \App\Models\Group::STATUT_FIN_FORMATION)
                                            <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $group->id }})"
                                                wire:loading.attr="disabled" wire:target="edit({{ $group->id }})">
                                                {{ __('Edit') }}
                                            </x-backoffice.ui.action-menu.item>
                                        @endif
                                    @endcan
                                </x-backoffice.ui.action-menu>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$groups" />
        @endif
    </x-backoffice.ui.card>

    {{-- Add/Edit modal --}}
    <div x-data="{ show: @entangle('showModal') }">
        <div x-cloak class="modal fade show" tabindex="-1" role="dialog"
            :style="show ? 'display:block; z-index:1060;' : 'display:none;'">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">{{ $editingId ? __('Edit Group') : __('Add Group') }}</h4>
                        <button type="button" class="btn-close custom-btn-close" wire:click="closeModal" aria-label="Close">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    <form wire:submit="save">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label" for="g-nom">{{ __('Name') }}<span class="text-danger ms-1">*</span></label>
                                        <input type="text" id="g-nom" wire:model="nom" class="form-control @error('nom') is-invalid @enderror"
                                            placeholder="{{ __('e.g. Herr Driss 13h - Intensifs') }}">
                                        @error('nom')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <x-backoffice.forms.select2 id="g-niveau" model="niveau"
                                        :label="__('Level')" required :placeholder="__('Choose…')">
                                        @foreach ($niveaux as $niv)<option value="{{ $niv }}">{{ $niv }}</option>@endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-6">
                                    <x-backoffice.forms.select2 id="g-ens" model="enseignant_id"
                                        :label="__('Teacher')" :placeholder="__('Choose…')">
                                        @foreach ($enseignants as $e)<option value="{{ $e->id }}">{{ $e->nomComplet() }}</option>@endforeach
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-6">
                                    <x-backoffice.forms.select2 id="g-statut" model="statut"
                                        :label="__('Status')" required>
                                        <option value="{{ \App\Models\Group::STATUT_PRE_INSCRIPTION }}">{{ \App\Models\Group::STATUT_PRE_INSCRIPTION }}</option>
                                        <option value="{{ \App\Models\Group::STATUT_EN_FORMATION }}">{{ \App\Models\Group::STATUT_EN_FORMATION }}</option>
                                    </x-backoffice.forms.select2>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="g-debut">{{ __('Start date') }}</label>
                                        <input type="date" id="g-debut" wire:model="date_debut_formation" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label" for="g-fin">{{ __('End date') }}</label>
                                        <input type="date" id="g-fin" wire:model="date_fin_formation" class="form-control @error('date_fin_formation') is-invalid @enderror">
                                        @error('date_fin_formation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Assigned fees --}}
                            <div class="border-top pt-3">
                                <h6 class="mb-1">{{ __('Group fees') }}</h6>
                                <p class="text-muted fs-13 mb-3">{{ __('Set the amount (and due date) of each fee for this group — amounts default to 0. All fees are carried to the enrollment when a student is assigned to this group.') }}</p>

                                @if (count($fraisLignes) === 0)
                                    <x-backoffice.ui.alert variant="warning" :dismissible="false">
                                        {{ __('No fees in the catalog yet. Add fees under Settings → Fees first.') }}
                                    </x-backoffice.ui.alert>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm align-middle text-center mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="text-center" style="width: 34%;">{{ __('Fee') }}</th>
                                                    <th class="text-center" style="width: 18%;">{{ __('Classification') }}</th>
                                                    <th class="text-center" style="width: 24%;">{{ __('Due date') }}</th>
                                                    <th class="text-center" style="width: 24%;">{{ __('Amount') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($fraisLignes as $fraisId => $ligne)
                                                    @if ($fraisCatalog->has($fraisId))
                                                        <tr wire:key="gf-{{ $fraisId }}">
                                                            <td>
                                                                <label class="form-label mb-0" for="gf-m-{{ $fraisId }}">{{ $fraisCatalog[$fraisId]->nom }}</label>
                                                            </td>
                                                            <td>
                                                                <select wire:model="fraisLignes.{{ $fraisId }}.classification"
                                                                    class="form-select form-select-sm text-center @error('fraisLignes.'.$fraisId.'.classification') is-invalid @enderror"
                                                                    title="{{ __('Classification') }}">
                                                                    <option value="">—</option>
                                                                    @foreach ($niveaux as $niv)<option value="{{ $niv }}">{{ $niv }}</option>@endforeach
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="date" wire:model="fraisLignes.{{ $fraisId }}.date_echeance"
                                                                    class="form-control form-control-sm text-center" title="{{ __('Due date') }}">
                                                            </td>
                                                            <td>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="number" step="0.01" min="0" id="gf-m-{{ $fraisId }}"
                                                                        wire:model="fraisLignes.{{ $fraisId }}.montant"
                                                                        class="form-control text-center @error('fraisLignes.'.$fraisId.'.montant') is-invalid @enderror"
                                                                        placeholder="0">
                                                                    <span class="input-group-text">DH</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
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
