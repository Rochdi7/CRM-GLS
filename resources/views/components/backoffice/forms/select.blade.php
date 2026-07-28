{{--
    Form select — Bootstrap select with label + validation error, for CLASSIC
    (non-Livewire) forms posting via old(). Add class="select" to activate
    Select2 with search (initialised by resources/js/backoffice/app.js on load
    AND on livewire:navigated; search box appears from 6 options, or force it
    with data-search="always"/"never").

    Select2 + Livewire: for a select bound to a Livewire property, use
    <x-backoffice.forms.select2> instead — it handles wire:ignore and the
    two-way sync (see CLAUDE.md § wire:ignore).

    Usage:
        <x-backoffice.forms.select name="status" :label="__('Status')"
            :options="['active' => __('Active'), 'inactive' => __('Inactive')]"
            :selected="old('status')" />

        Custom options markup:
        <x-backoffice.forms.select name="group" :label="__('Group')">
            <option value="">{{ __('Choose…') }}</option>
            …
        </x-backoffice.forms.select>
--}}
@props(['name', 'label' => null, 'options' => [], 'selected' => null, 'required' => false, 'placeholder' => null])

<div class="mb-3">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}@if ($required)<span class="text-danger ms-1">*</span>@endif
        </label>
    @endif
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @required($required)
        {{ $attributes->merge(['class' => 'form-select'.($errors->has($name) ? ' is-invalid' : '')]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @forelse ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) old($name, $selected) === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @empty
            {{ $slot }}
        @endforelse
    </select>
    <x-backoffice.forms.error :name="$name" />
</div>
