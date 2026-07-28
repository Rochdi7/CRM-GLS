{{--
    Card — PreSkool/Bootstrap card wrapper.

    Usage:
        <x-backoffice.ui.card :title="__('Students')">
            <x-slot:tools>…header-right buttons…</x-slot:tools>
            body content
            <x-slot:footer>…</x-slot:footer>
        </x-backoffice.ui.card>
--}}
@props(['title' => null, 'flush' => false])

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || isset($tools))
        <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
            @if ($title)
                <h4 class="mb-3">{{ $title }}</h4>
            @endif
            @isset($tools)
                <div class="d-flex align-items-center flex-wrap mb-3">{{ $tools }}</div>
            @endisset
        </div>
    @endif
    <div @class(['card-body', 'p-0' => $flush])>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="card-footer">{{ $footer }}</div>
    @endisset
</div>
