{{--
    Filter bar — labeled row of list filters, rendered above a table.

    Unlike cramming select2/date/search controls into the card header's
    `tools` slot (they fight the title for space and wrap into an unlabeled,
    uneven stack on narrower cards), this renders its own full-width row
    inside the card body: each filter gets a small label above it and the
    fields lay out in a responsive grid, so they never collide with the title.

    Usage:
        <x-backoffice.ui.card :title="__('Expenses')">
            <x-backoffice.ui.filter-bar>
                <x-backoffice.forms.select2 id="d-type-filter" model="typeFilter" live
                    :label="__('Expense type')" :placeholder="__('All expense types')">
                    …
                </x-backoffice.forms.select2>

                <x-backoffice.ui.filter-bar.date-field :label="__('From date')" model="dateFrom" />
                <x-backoffice.ui.filter-bar.date-field :label="__('To date')" model="dateTo" />

                <x-slot:search>
                    <input type="text" class="form-control" wire:model.live.debounce.400ms="search" placeholder="{{ __('Search') }}">
                </x-slot:search>
                <x-slot:actions>
                    <button type="button" class="btn btn-primary" wire:click="create">{{ __('Record Expense') }}</button>
                </x-slot:actions>
            </x-backoffice.ui.filter-bar>
        </x-backoffice.ui.card>

    Every direct child of the default slot should be a labeled block that
    manages its own width (select2 without `inline` keeps its own label;
    <x-backoffice.ui.filter-bar.date-field> is the ready-made date block) —
    each is placed in its own `col-auto` so the row wraps field-by-field
    instead of splitting a single control's label from its input.
    `search` and `actions` are optional named slots rendered at the row's end.
--}}
@props(['search' => null, 'actions' => null])

<div class="d-flex align-items-end flex-wrap gap-3 mb-3 gls-filter-bar">
    {{ $slot }}

    @isset($search)
        <div style="width: 260px; max-width: 100%;">
            {{ $search }}
        </div>
    @endisset

    @isset($actions)
        <div class="ms-lg-auto">
            {{ $actions }}
        </div>
    @endisset
</div>
