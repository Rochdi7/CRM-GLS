{{--
    Cash registers — READ-ONLY list.
    A caisse is created automatically with its employee (CaisseProvisioner):
    no add / edit / delete action exists here by design.
    Rendered as the « Comptes de caisse » tab of the Gestion de la caisse page
    (backoffice/caisses/index.blade.php) — the page owns the header.
--}}
<div>
    <x-backoffice.ui.card :title="__('Cash Registers')">
        <x-backoffice.ui.filter-bar>
            <x-backoffice.forms.select2 id="c-etab-filter" model="etablissementFilter" live
                :label="__('Center')" width="180px" :placeholder="__('All centers')">
                @foreach ($etablissements as $etab)
                    <option value="{{ $etab->id }}">{{ $etab->nom_centre }}</option>
                @endforeach
            </x-backoffice.forms.select2>
            <x-backoffice.forms.select2 id="c-statut-filter" model="statutFilter" live
                :label="__('Status')" width="150px" :placeholder="__('All statuses')">
                @foreach ($statuts as $st)
                    <option value="{{ $st }}">{{ $st }}</option>
                @endforeach
            </x-backoffice.forms.select2>
            <x-slot:search>
                <div class="input-icon-start position-relative">
                    <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </div>
            </x-slot:search>
        </x-backoffice.ui.filter-bar>

        <x-backoffice.ui.alert variant="info" :dismissible="false">
            {{ __('Each employee owns one cash register, created automatically with them. Balances only move through payments, expenses, refunds and validated transfers.') }}
        </x-backoffice.ui.alert>

        @if ($caisses->isEmpty())
            <x-backoffice.ui.empty-state :title="__('No cash registers yet')"
                :message="__('A cash register appears here as soon as an employee is created.')" icon="ti ti-cash" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Center') }}</th>
                        <th>{{ __('Manager') }}</th>
                        <th class="text-end">{{ __('Balance') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="text-end">{{ __('Action') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($caisses as $caisse)
                    <tr wire:key="cai-{{ $caisse->id }}">
                        <td class="fw-medium">
                            <a href="{{ route('backoffice.caisses.show', $caisse) }}" class="text-dark">{{ $caisse->nom }}</a>
                        </td>
                        <td>{{ $caisse->etablissement?->nom_centre ?? '—' }}</td>
                        <td>{{ $caisse->responsable?->nomComplet() ?? '—' }}</td>
                        <td class="text-end fw-medium">{{ number_format((float) $caisse->solde, 2) }} DH</td>
                        <td>
                            <span class="badge {{ $caisse->statut === \App\Models\Caisse::STATUT_ACTIVE ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                                {{ $caisse->statut }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('backoffice.caisses.show', $caisse) }}" class="btn btn-light btn-sm">
                                <i class="ti ti-eye me-1"></i>{{ __('View') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$caisses">
                <x-backoffice.ui.per-page-select />
            </x-backoffice.ui.pagination>
        @endif
    </x-backoffice.ui.card>
</div>
