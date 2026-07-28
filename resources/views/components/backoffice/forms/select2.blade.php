{{--
    Select2 select bound two-way to a Livewire property.

    The <select> lives in a wire:ignore island — Select2 replaces it with its
    own markup, so Livewire must never morph it (CLAUDE.md §7). The glsSelect2
    Alpine component (resources/js/backoffice/app.js) syncs the widget with the
    @entangle'd property in both directions, so server-side changes (edit modal
    prefill, resets) show up in the widget and user picks reach Livewire.

    Usage:
        <x-backoffice.forms.select2 id="emp-etab" model="etablissement_id"
            :label="__('Center')" :placeholder="__('Choose…')">
            @foreach ($etablissements as $etab)
                <option value="{{ $etab->id }}">{{ $etab->nom_centre }}</option>
            @endforeach
        </x-backoffice.forms.select2>

    Props:
        id          unique DOM id (required — label target)
        model       Livewire property name (required)
        live        true → sync on change (wire:model.live equivalent)
        options     [value => label] map; or put <option>s in the slot
        placeholder rendered as first <option value=""> (use for "Choose…"/"All …")
        search      'auto' (box appears from 6 options), 'always', 'never'
        inline      true → bare control for toolbars/filters (no label, no mb-3)
        width       min-width applied to the wrapper (inline filters)
        error       error-bag key when it differs from `model`
--}}
@props([
    'id',
    'model',
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'required' => false,
    'live' => false,
    'search' => 'auto',
    'inline' => false,
    'width' => null,
    'error' => null,
])
@php
    $errorKey = $error ?? $model;

    /*
     * The <option> list is assembled here and echoed as one string on purpose.
     * Blade directives (@if/@forelse) inside a Livewire view emit
     * `<!--[if BLOCK]><![endif]-->` morph markers; sitting between the <option>
     * nodes those comments break Select2's option parsing and the widget shows
     * "No results found" even though the options are present in the HTML.
     */
    $optionsHtml = '';

    if ($placeholder !== null) {
        $optionsHtml .= '<option value="">'.e($placeholder).'</option>';
    }

    if (filled($options)) {
        foreach ($options as $optionValue => $optionLabel) {
            $optionsHtml .= '<option value="'.e($optionValue).'">'.e($optionLabel).'</option>';
        }
    } else {
        // Slot markup is author-controlled Blade (escaped at the call site).
        $optionsHtml .= $slot->toHtml();
    }

    // Callers build their <option>s with @foreach, which leaves the same morph
    // markers inside the slot — strip every comment node.
    $optionsHtml = preg_replace('/<!--.*?-->/s', '', $optionsHtml);
@endphp

<div @class(['mb-3' => ! $inline]) @if ($width) style="min-width: {{ $width }};" @endif>
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}@if ($required)<span class="text-danger ms-1">*</span>@endif
        </label>
    @endif
    {{-- This wrapper is still morphed by Livewire → validation state stays fresh --}}
    <div @class(['gls-select2', 'is-invalid' => $errors->has($errorKey)])>
        {{-- wire:ignore: Select2 owns this DOM; sync handled by glsSelect2 --}}
        <div wire:ignore x-data="glsSelect2(@entangle($model){{ $live ? '.live' : '' }})">
            <select
                x-ref="select"
                id="{{ $id }}"
                data-search="{{ $search }}"
                @required($required)
                {{ $attributes->merge(['class' => 'form-select']) }}
            >{!! $optionsHtml !!}</select>
        </div>
    </div>
    @unless ($inline)
        @error($errorKey)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @endunless
</div>
