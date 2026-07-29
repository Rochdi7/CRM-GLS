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

    Note: deliberately NOT class="datatable" — the vendor script.js calls
    $('.datatable').DataTable() unconditionally, but the DataTables plugin
    is never loaded (server-side pagination is used instead per the rule
    above), so that class threw a TypeError on every page using this
    component and aborted the rest of script.js's ready handler.
--}}
@props(['hover' => true])

<div class="table-responsive">
    <table {{ $attributes->merge(['class' => 'table'.($hover ? ' table-hover' : '')]) }}>
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
