{{--
    419 — expired CSRF token, i.e. a page left open too long. Users read
    Laravel's default "Page Expired" as a crash; it is simply a re-login.
--}}
@extends('errors.layout')

@section('title', __('Session expired'))
@section('illustration', asset('assets/crm-gls/img/authentication/error-500.svg'))
@section('heading', __('Your session has expired'))

@section('message')
    {{ __("This page stayed open too long and the security token is no longer valid. Nothing was saved.") }}
@endsection

@section('reassurance')
    {{ __("Sign in again and redo the action — this is normal after a long period of inactivity.") }}
@endsection

@section('actions')
    <a href="{{ route('backoffice.login') }}" class="btn btn-primary d-inline-flex align-items-center">
        <i class="ti ti-login me-2"></i>{{ __('Sign in again') }}
    </a>
@endsection
