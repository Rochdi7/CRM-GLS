{{--
    Read-only archive of finished groups (schema §7).
    Rows come exclusively from Group::archiverCommeTermine() — no CRUD here.
--}}
<x-backoffice.layout.app :title="__('Groups History')">
    <x-backoffice.layout.page-header
        :title="__('Groups History')"
        :breadcrumbs="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Groups') => route('backoffice.groups.index'),
            __('History') => null,
        ]" />

    <x-backoffice.ui.card :title="__('Archived groups')">
        @if ($historiques->isEmpty())
            <x-backoffice.ui.empty-state
                :title="__('No archived groups yet')"
                :message="__('A group appears here once it is closed with “Fin de formation”.')"
                icon="ti ti-history" />
        @else
            <x-backoffice.ui.table>
                <x-slot:head>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Level') }}</th>
                        <th>{{ __('Teacher') }}</th>
                        <th>{{ __('Center') }}</th>
                        <th>{{ __('Academic Year') }}</th>
                        <th>{{ __('Students') }}</th>
                        <th>{{ __('Period') }}</th>
                        <th>{{ __('Archived at') }}</th>
                        <th>{{ __('Archived by') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($historiques as $historique)
                    <tr wire:key="gh-{{ $historique->id }}">
                        <td class="fw-medium">
                            @if ($historique->group)
                                <a href="{{ route('backoffice.groups.show', $historique->group) }}">{{ $historique->nom }}</a>
                            @else
                                {{ $historique->nom }}
                            @endif
                        </td>
                        <td><span class="badge badge-soft-info">{{ $historique->niveau }}</span></td>
                        <td>{{ $historique->enseignant?->nomComplet() ?? '—' }}</td>
                        <td>{{ $historique->etablissement?->nom_centre ?? '—' }}</td>
                        <td>{{ $historique->anneeScolaire?->nom ?? '—' }}</td>
                        <td><span class="badge badge-soft-secondary">{{ $historique->nombre_etudiants_final }}</span></td>
                        <td>
                            {{ $historique->date_debut_formation?->format('d/m/Y') ?? '—' }}
                            &rarr;
                            {{ $historique->date_fin_formation?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td>{{ $historique->archived_at?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $historique->archivedBy?->nomComplet() ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-backoffice.ui.table>
            <x-backoffice.ui.pagination :paginator="$historiques" />
        @endif
    </x-backoffice.ui.card>
</x-backoffice.layout.app>
