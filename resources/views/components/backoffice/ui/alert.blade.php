{{--
    Alert — Bootstrap dismissible alert, PreSkool markup.

    Usage:
        <x-backoffice.ui.alert variant="success">{{ __('Saved.') }}</x-backoffice.ui.alert>
        <x-backoffice.ui.alert variant="danger" :dismissible="false">…</x-backoffice.ui.alert>
--}}
@props(['variant' => 'info', 'dismissible' => true])

<div {{ $attributes->merge(['class' => "alert alert-{$variant} d-flex align-items-center justify-content-between"]) }} role="alert">
    <div class="d-flex align-items-center">
        {{ $slot }}
    </div>
    @if ($dismissible)
        <button type="button" class="btn-close p-0" data-bs-dismiss="alert" aria-label="Close">
            <span><i class="ti ti-x"></i></span>
        </button>
    @endif
</div>
