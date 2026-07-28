{{--
    Modal — Bootstrap modal, PreSkool markup (see theme-reference ui/ui-modals).

    Usage:
        <x-backoffice.ui.modal id="add_student" :title="__('Add Student')">
            …body…
            <x-slot:footer>
                <x-backoffice.ui.button variant="light" type="button" data-bs-dismiss="modal">{{ __('Cancel') }}</x-backoffice.ui.button>
                <x-backoffice.ui.button>{{ __('Save') }}</x-backoffice.ui.button>
            </x-slot:footer>
        </x-backoffice.ui.modal>

    Trigger: <button data-bs-toggle="modal" data-bs-target="#add_student">…

    Livewire note: for modals whose content is Livewire-driven, wrap the plugin-
    controlled parts with wire:ignore and document it (see CLAUDE.md § wire:ignore).
--}}
@props(['id', 'title' => null, 'size' => null, 'centered' => true])

<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div @class([
        'modal-dialog',
        'modal-dialog-centered' => $centered,
        "modal-{$size}" => $size,
    ])>
        <div class="modal-content">
            @if ($title)
                <div class="modal-header">
                    <h4 class="modal-title">{{ $title }}</h4>
                    <button type="button" class="btn-close custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ti ti-x"></i>
                    </button>
                </div>
            @endif
            <div class="modal-body">
                {{ $slot }}
            </div>
            @isset($footer)
                <div class="modal-footer">{{ $footer }}</div>
            @endisset
        </div>
    </div>
</div>
