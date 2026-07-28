{{--
    Table — PreSkool table design WITHOUT client-side DataTables data handling.
    Large CRM lists must use Livewire server-side pagination/search/sort
    (see CLAUDE.md § DataTables rule).

    Usage:
        <x-backoffice.ui.table>
            <x-slot:head>
                <tr><th>{{ __('Name') }}</th><th>…</th></tr>
            </x-slot:head>
            <tr><td>…</td></tr>
        </x-backoffice.ui.table>
--}}
@props(['hover' => true])

<div class="table-responsive">
    <table {{ $attributes->merge(['class' => 'table datatable'.($hover ? ' table-hover' : '')]) }}>
        @isset($head)
            <thead class="thead-light">
                {{ $head }}
            </thead>
        @endisset
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
