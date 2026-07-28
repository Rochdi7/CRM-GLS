{{--
    Validation error message for a single field.

    Usage:
        <x-backoffice.forms.error name="email" />
--}}
@props(['name'])

@error($name)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
