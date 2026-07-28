{{--
    Breadcrumb trail — markup taken from the PreSkool page headers.

    Usage:
        <x-backoffice.layout.breadcrumbs :items="[
            __('Dashboard') => route('backoffice.dashboard'),
            __('Students')  => null,   // null → active (current) item
        ]" />
--}}
@props(['items' => []])

<nav>
    <ol class="breadcrumb mb-0">
        @foreach ($items as $label => $url)
            @if ($url)
                <li class="breadcrumb-item"><a href="{{ $url }}">{{ $label }}</a></li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
            @endif
        @endforeach
    </ol>
</nav>
