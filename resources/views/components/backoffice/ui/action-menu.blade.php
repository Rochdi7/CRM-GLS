{{--
    Row action menu — PreSkool pattern (theme-reference/preskool/students/
    students.blade.php): the eye (view) button stays visible next to a 3-dots
    dropdown that holds every other row action.

    Usage:
        <x-backoffice.ui.action-menu :view="route('backoffice.students.show', $student)">
            @can('update', $student)
                <x-backoffice.ui.action-menu.item icon="ti-edit" wire:click="edit({{ $student->id }})">
                    {{ __('Edit') }}
                </x-backoffice.ui.action-menu.item>
            @endcan
        </x-backoffice.ui.action-menu>

    Props:
        view       detail-page URL — renders the eye button; omit when the
                   resource has no detail page (dots only)
        viewLabel  eye button title (default __('View'))

    The dropdown renders only when the slot has content, so rows where every
    action is denied by @can show just the eye (or nothing).
--}}
@props(['view' => null, 'viewLabel' => null])

<div class="d-flex align-items-center justify-content-end">
    @if ($view)
        <a href="{{ $view }}"
            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-2"
            title="{{ $viewLabel ?? __('View') }}">
            <i class="ti ti-eye"></i>
        </a>
    @endif
    @if (trim($slot) !== '')
        <div class="dropdown">
            <a href="javascript:void(0);"
                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Actions') }}">
                <i class="ti ti-dots-vertical fs-14"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end p-3">
                {{ $slot }}
            </ul>
        </div>
    @endif
</div>
