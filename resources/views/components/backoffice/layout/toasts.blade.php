{{--
    Global toast notifications (Bootstrap 5 toast, screenshot style: logo +
    "GLS Backoffice" header + message body).

    Two feeding channels, both handled in resources/js/backoffice/app.js:
    - Session flashes (controller redirects): rendered below as hidden
      [data-gls-flash-toast] nodes, converted to toasts on page load.
    - Livewire actions (modal create/edit/delete): components dispatch
      $this->dispatch('toast', type: …, message: …) caught via Livewire.on().
--}}
{{-- top: 70px keeps toasts below the fixed PreSkool header bar. --}}
<div id="gls-toasts"
     class="toast-container position-fixed end-0 p-3"
     style="z-index: 1090; top: 70px;"
     data-app-name="GLS Backoffice"
     data-logo="{{ asset('assets/images/logo/gls-noir.png') }}"
     data-label-just-now="{{ __('Just now') }}"
     data-label-close="{{ __('Close') }}">
    @foreach (['status' => 'success', 'settings_status' => 'success', 'profile_status' => 'success', 'password_status' => 'success', 'error' => 'danger'] as $key => $type)
        @if (session($key))
            <div data-gls-flash-toast
                 data-type="{{ $type }}"
                 data-message="{{ session($key) }}"
                 hidden></div>
        @endif
    @endforeach
</div>
