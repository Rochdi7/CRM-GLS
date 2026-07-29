{{--
    Tag-chips input bound two-way to a Livewire property (bootstrap-tagsinput).

    The input lives in a wire:ignore island — the plugin replaces it with its
    chip widget, so Livewire must never morph it (CLAUDE.md §7). The
    glsTagsInput Alpine bridge (resources/js/backoffice/app.js) keeps the
    widget and the @entangle'd property in sync in both directions. The
    property holds the stored comma-separated string; the user only sees
    chips (Enter or comma adds a tag, × removes it).

    The page must push the plugin assets:
        @push('styles')  → assets/preskool/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css
        @push('scripts') → assets/preskool/plugins/bootstrap-tagsinput/bootstrap-tagsinput.min.js

    Props:
        id          unique DOM id (required — label target)
        model       Livewire property name (required)
        error       error-bag key when it differs from `model`
--}}
@props([
    'id',
    'model',
    'label' => null,
    'placeholder' => null,
    'required' => false,
    'error' => null,
])
@php $errorKey = $error ?? $model; @endphp

<div class="mb-3">
    @if ($label)
        <label for="{{ $id }}" class="form-label">
            {{ $label }}@if ($required)<span class="text-danger ms-1">*</span>@endif
        </label>
    @endif
    {{-- This wrapper is still morphed by Livewire → validation state stays fresh --}}
    <div @class(['gls-tags-input', 'is-invalid' => $errors->has($errorKey)])>
        {{-- wire:ignore: bootstrap-tagsinput owns this DOM; sync handled by glsTagsInput --}}
        <div wire:ignore x-data="glsTagsInput(@entangle($model))">
            <input
                type="text"
                x-ref="input"
                id="{{ $id }}"
                @if ($placeholder) placeholder="{{ $placeholder }}" @endif
                {{ $attributes->merge(['class' => 'input-tags form-control']) }}
            >
        </div>
    </div>
    @error($errorKey)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
</div>
