{{--
    Labeled date filter, for use inside <x-backoffice.ui.filter-bar>.

    Usage:
        <x-backoffice.ui.filter-bar.date-field :label="__('From date')" model="dateFrom" />
        <x-backoffice.ui.filter-bar.date-field :label="__('From date')" model="dateFrom" debounce="400ms" />
--}}
@props(['label', 'model', 'width' => '160px', 'debounce' => null])

<div style="min-width: {{ $width }};">
    <label class="form-label">{{ $label }}</label>
    <input type="date" class="form-control" wire:model.live{{ $debounce ? '.debounce.'.$debounce : '' }}="{{ $model }}">
</div>
