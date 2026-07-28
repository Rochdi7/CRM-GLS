<x-backoffice.layout.app :title="$group->nom">
    <x-backoffice.layout.page-header
        :title="$group->nom"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Groups') => route('backoffice.groups.index'),
            $group->nom => null,
        ]">
        @can('archive', $group)
            @if ($group->statut !== \App\Models\Group::STATUT_FIN_FORMATION)
                <x-slot:actions>
                    <form method="POST" action="{{ route('backoffice.groups.archive', $group) }}"
                        onsubmit="return confirm('{{ __('Mark this group as finished (Fin de formation)? This cannot be undone.') }}');">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary d-flex align-items-center">
                            <i class="ti ti-archive me-2"></i>{{ __('End training') }}
                        </button>
                    </form>
                </x-slot:actions>
            @endif
        @endcan
    </x-backoffice.layout.page-header>

    <div class="row">
        <div class="col-xl-4">
            <x-backoffice.ui.card :title="__('Group')">
                @foreach ([
                    __('Level') => $group->niveau,
                    __('Teacher') => $group->enseignant?->nomComplet(),
                    __('Center') => $group->etablissement?->nom_centre,
                    __('Academic Year') => $group->anneeScolaire?->nom,
                    __('Start date') => $group->date_debut_formation?->format('d/m/Y'),
                    __('End date') => $group->date_fin_formation?->format('d/m/Y'),
                ] as $label => $value)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="fw-medium text-end">{{ $value ?: '—' }}</span>
                    </div>
                @endforeach
                <div class="d-flex justify-content-between">
                    <span class="text-muted">{{ __('Status') }}</span>
                    <span class="badge badge-soft-info">{{ $group->statut }}</span>
                </div>
            </x-backoffice.ui.card>

            {{-- Assigned fees --}}
            <x-backoffice.ui.card :title="__('Assigned fees')">
                @if ($group->frais->isEmpty())
                    <p class="text-muted mb-0">{{ __('No fees assigned.') }}</p>
                @else
                    @foreach ($group->frais as $f)
                        <div class="d-flex justify-content-between mb-2">
                            <span>
                                {{ $f->nom }}
                                @if ($f->pivot->classification)
                                    <span class="badge badge-soft-info ms-1">{{ $f->pivot->classification }}</span>
                                @endif
                            </span>
                            <span class="fw-medium">{{ number_format((float) $f->pivot->montant, 2) }} DH</span>
                        </div>
                    @endforeach
                @endif
            </x-backoffice.ui.card>
        </div>

        {{-- Students --}}
        <div class="col-xl-8">
            <x-backoffice.ui.card :title="__('Students')">
                <x-slot:tools>
                    <span class="badge badge-soft-secondary">{{ $group->inscriptions->count() }}</span>
                </x-slot:tools>
                @if ($group->inscriptions->isEmpty())
                    <x-backoffice.ui.empty-state :title="__('No students enrolled')" icon="ti ti-users" />
                @else
                    <x-backoffice.ui.table>
                        <x-slot:head>
                            <tr>
                                <th>{{ __('Reference') }}</th>
                                <th>{{ __('Student') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </x-slot:head>
                        @foreach ($group->inscriptions as $insc)
                            <tr>
                                <td><code>{{ $insc->reference }}</code></td>
                                <td>
                                    <a href="{{ route('backoffice.students.show', $insc->student) }}">{{ $insc->student?->nomComplet() }}</a>
                                </td>
                                <td>{{ $insc->date_inscription?->format('d/m/Y') }}</td>
                                <td><span class="badge badge-soft-info">{{ $insc->statut }}</span></td>
                            </tr>
                        @endforeach
                    </x-backoffice.ui.table>
                @endif
            </x-backoffice.ui.card>
        </div>
    </div>
</x-backoffice.layout.app>
