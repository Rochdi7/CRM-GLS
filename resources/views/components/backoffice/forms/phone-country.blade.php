{{--
    Single "Pays" (country dial code) selector shared by ALL phone inputs on
    the form. Bind once; every <x-backoffice.forms.phone-input> then shows the
    matching +xxx prefix. Requires the component to use WithPhoneCountry.
--}}
@props(['id' => 'phone-pays', 'label' => null])

<x-backoffice.forms.select2 :id="$id" model="phonePays" live search="always"
    :label="$label ?? __('Country')">
    @foreach (\App\Support\Phone\Countries::all() as $iso => $c)
        <option value="{{ $iso }}">{{ $c['nom'] }} {{ $c['dial'] }}</option>
    @endforeach
</x-backoffice.forms.select2>
