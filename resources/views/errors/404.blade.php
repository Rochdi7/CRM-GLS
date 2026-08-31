@extends('errors.layout')

@section('title', __('Page not found'))
@section('illustration', asset('assets/crm-gls/img/authentication/error-404.svg'))
@section('heading', __('This page does not exist'))

@section('message')
    {{ __("The address is incorrect, or the record you are looking for has been moved or deleted.") }}
@endsection

@section('reassurance')
    {{ __("The application is working normally — only this address is invalid.") }}
@endsection

@section('actions')
    <a href="{{ route('backoffice.dashboard') }}" class="btn btn-primary d-inline-flex align-items-center">
        <i class="ti ti-home me-2"></i>{{ __('Back to dashboard') }}
    </a>
@endsection
