@extends('errors.layout')

@section('title', __('Too many attempts'))
@section('illustration', asset('assets/crm-gls/img/authentication/error-500.svg'))
@section('heading', __('Too many attempts'))

@section('message')
    {{ __("Too many requests were sent in a short time. This protection is temporary.") }}
@endsection

@section('reassurance')
    {{ __("Wait a moment, then try again.") }}
@endsection

@section('actions')
    <a href="{{ route('backoffice.dashboard') }}" class="btn btn-primary d-inline-flex align-items-center">
        <i class="ti ti-home me-2"></i>{{ __('Back to dashboard') }}
    </a>
@endsection
