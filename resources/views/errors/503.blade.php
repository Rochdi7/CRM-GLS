{{--
    503 — the ONE page that should say the application is unavailable, because
    here it genuinely is (artisan down / maintenance during a deploy). Kept
    honest and distinct from 500 so the two are never confused again.
--}}
@extends('errors.layout')

@section('title', __('Maintenance in progress'))
@section('illustration', asset('assets/crm-gls/img/authentication/under-maintanence.svg'))
@section('heading', __('Maintenance in progress'))

@section('message')
    {{ __("The application is temporarily unavailable while an update is being installed.") }}
@endsection

@section('reassurance')
    {{ __("This is a planned operation and usually takes only a few minutes. Your data is safe.") }}
@endsection

@section('actions')
    <a href="{{ url()->current() }}" class="btn btn-primary d-inline-flex align-items-center">
        <i class="ti ti-refresh me-2"></i>{{ __('Retry') }}
    </a>
@endsection
