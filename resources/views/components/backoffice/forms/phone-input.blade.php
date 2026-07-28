{{--
    Phone number input prefixed with the shared country dial code.
    The country is chosen ONCE via <x-backoffice.forms.phone-country>; every
    phone-input on the form shares that dial code (WithPhoneCountry::$phonePays).
    This component keeps only the national part in its Livewire property.
--}}
@props([
    'label',
    'model',           // Livewire property holding the national number, e.g. "telephone"
    'pays' => null,    // shared ISO2 country (drives the +xxx prefix)
    'id',
    'required' => false,
])

@php $dial = \App\Support\Phone\Countries::dial($pays); @endphp

<div class="mb-3">
    <label class="form-label" for="{{ $id }}">{{ $label }}@if ($required)<span class="text-danger ms-1">*</span>@endif</label>
    <div class="input-group">
        <span class="input-group-text">{{ $dial }}</span>
        <input type="tel" id="{{ $id }}" wire:model="{{ $model }}"
            class="form-control @error($model) is-invalid @enderror"
            placeholder="{{ __('ex : 661954125') }}">
        @error($model)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
