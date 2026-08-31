{{--
    500 — the page users mistook for "the server is down" (31/08/2026).

    The wording therefore states three things in order: this ONE action failed,
    the rest of the CRM is still running, and nothing was half-saved (every
    money/state write is wrapped in a transaction, CLAUDE.md §11 — so a 500
    always rolls back rather than leaving a partial record). The support
    reference is the Laravel exception id already written to the log, which
    turns "ça marche pas" into a line the maintainer can grep for.
--}}
@extends('errors.layout')

@section('title', __('Action failed'))
@section('illustration', asset('assets/crm-gls/img/authentication/error-500.svg'))
@section('heading', __("This action could not be completed"))

@section('message')
    {{ __("An unexpected error interrupted this operation. Nothing was saved: the action was cancelled in full.") }}
@endsection

@section('reassurance')
    {{ __("The application is still running — only this action failed. You can go back and continue working.") }}
@endsection

@section('actions')
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('backoffice.dashboard') }}"
       class="btn btn-primary d-inline-flex align-items-center">
        <i class="ti ti-arrow-left me-2"></i>{{ __('Go back') }}
    </a>
    <a href="{{ route('backoffice.dashboard') }}" class="btn btn-outline-secondary d-inline-flex align-items-center">
        <i class="ti ti-home me-2"></i>{{ __('Back to dashboard') }}
    </a>
@endsection

@section('meta')
    <p class="fs-13 text-muted mb-1">
        {{ __("If it happens again, report this reference to your administrator:") }}
    </p>
    <code class="fs-14 text-normal-case">{{ \App\Support\Errors\ErrorReference::current() }}</code>
@endsection
