{{--
    Form input — Bootstrap input group with label + validation error.

    Usage:
        <x-backoffice.forms.input name="first_name" :label="__('First Name')" required />
        <x-backoffice.forms.input name="email" type="email" :label="__('Email')" :value="old('email')" />
--}}
@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'required' => false, 'help' => null])

<div class="mb-3">
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}@if ($required)<span class="text-danger ms-1">*</span>@endif
        </label>
    @endif
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @required($required)
        {{ $attributes->merge(['class' => 'form-control'.($errors->has($name) ? ' is-invalid' : '')]) }}
    >
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
    <x-backoffice.forms.error :name="$name" />
</div>
