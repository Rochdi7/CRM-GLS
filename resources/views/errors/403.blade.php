{{--
    403 — a real permission refusal (CLAUDE.md §16). Distinct from 500 on
    purpose: here the app worked correctly and deliberately said no, so the
    wording must NOT apologise for a failure or suggest retrying.
--}}
@extends('errors.layout')

@section('title', __('Access denied'))
@section('illustration', asset('assets/crm-gls/img/authentication/error-404.svg'))
@section('heading', __('You do not have access to this page'))

@section('message')
    {{ $exception?->getMessage() ?: __("Your account does not hold the permission required for this section.") }}
@endsection

@section('reassurance')
    {{ __("This is not a malfunction. If you need this access, ask your administrator to grant it.") }}
@endsection

@section('actions')
    <a href="{{ route('backoffice.dashboard') }}" class="btn btn-primary d-inline-flex align-items-center">
        <i class="ti ti-home me-2"></i>{{ __('Back to dashboard') }}
    </a>
@endsection
