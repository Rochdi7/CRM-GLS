{{--
    Form textarea — Bootstrap textarea with label + validation error.

    Usage:
        <x-backoffice.forms.textarea name="notes" :label="__('Notes')" rows="4" />
--}}
@props(['name', 'label' => null, 'value' => null, 'required' => false, 'rows' => 3])

<div class="mb-3">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}@if ($required)<span class="text-danger ms-1">*</span>@endif
        </label>
    @endif
    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @required($required)
        {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}
    >{{ old($name, $value) }}</textarea>
    <x-backoffice.forms.error :name="$name" />
</div>
