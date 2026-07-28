<?php $page = 'ui-avatar'; ?>
@extends('layout.mainlayout')
@section('content')
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
       Avatars
        @endslot
    @endcomponent	
        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card card-h-100">
                    <div class="card-header">
                        <h5 class="card-title">Avatars</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align flex-wrap gap-2">
                            <span class="avatar avatar-xl me-2 avatar-rounded">
                                <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-xl me-2 avatar-radius-0">
                                <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-xl me-2">
                                <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="img">
                            </span>
                            <span class="avatar avatar-xl bg-primary avatar-rounded">
                                <span class="avatar-title">SR</span>
                            </span>
                            <span class="avatar avatar-xl bg-success avatar-radius-0">
                                <span class="avatar-title">SR</span>
                            </span>
                            <span class="avatar avatar-xl bg-danger">
                                <span class="avatar-title">SR</span>
                            </span>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Avatar Sizes</h5>
                    </div>
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar avatar-xs me-2">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-sm me-2">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-md me-2">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-lg me-2">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-xl me-2">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-xxl me-2">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                        </span>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->
            
        <!-- start row -->
        <div class="row">

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Avatar with Status</h5>
                    </div>
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar avatar-xs me-2 online avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-sm online me-2 avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-md me-2 online avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-lg me-2 away avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-xl me-2 online avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="avatar">
                        </span>
                        <span class="avatar avatar-xxl me-2 offline avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="avatar">
                        </span>
                        
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Avatar with Badge</h5>
                    </div>
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar avatar-xs me-2 avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                            <span class="badge rounded-pill bg-primary avatar-notification">2</span>
                        </span>
                        <span class="avatar avatar-sm me-2 avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                            <span class="badge rounded-pill bg-secondary avatar-notification">5</span>
                        </span>
                        <span class="avatar avatar-md me-2 avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                            <span class="badge rounded-pill bg-warning avatar-notification">1</span>
                        </span>
                        <span class="avatar avatar-lg me-2 avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                            <span class="badge rounded-pill bg-info avatar-notification">7</span>
                        </span>
                        <span class="avatar avatar-xl me-2 avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                            <span class="badge rounded-pill bg-success avatar-notification">3</span>
                        </span>
                        <span class="avatar avatar-xxl me-2 avatar-rounded">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="avatar">
                            <span class="badge rounded-pill bg-danger avatar-notification">9</span>
                        </span>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Solid Background Color</h5>
                    </div>
                    <div class="card-body">
                        <span class="avatar bg-primary avatar-rounded">
                            <span class="avatar-title">JD</span>
                        </span>
                        <span class="avatar bg-secondary avatar-rounded">
                            <span class="avatar-title">SR</span>
                        </span>
                        <span class="avatar bg-success avatar-rounded">
                            <span class="avatar-title">BJ</span>
                        </span>
                        <span class="avatar bg-info avatar-rounded">
                            <span class="avatar-title">AD</span>
                        </span>
                        <span class="avatar bg-warning avatar-rounded">
                            <span class="avatar-title">CB</span>
                        </span>
                        <span class="avatar bg-danger avatar-rounded">
                            <span class="avatar-title">KL</span>
                        </span>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Soft Background Color</h5>
                    </div>
                    <div class="card-body">
                        <span class="avatar bg-primary-subtle rounded">
                            <span class="avatar-title text-primary">JD</span>
                        </span>
                        <span class="avatar bg-secondary-subtle rounded">
                            <span class="avatar-title text-secondary">SR</span>
                        </span>
                        <span class="avatar bg-success-subtle rounded">
                            <span class="avatar-title text-success">BJ</span>
                        </span>
                        <span class="avatar bg-info-subtle rounded">
                            <span class="avatar-title text-info">AD</span>
                        </span>
                        <span class="avatar bg-warning-subtle rounded">
                            <span class="avatar-title text-warning">CB</span>
                        </span>
                        <span class="avatar bg-danger-subtle rounded">
                            <span class="avatar-title text-danger">KL</span>
                        </span>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Avatar Group - Square</h5>
                    </div>
                    <div class="card-body">
                        <div class="avatar-list-stacked avatar-group-lg mb-3">
                            <span class="avatar">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="avatar">
                            </span>
                            <a class="avatar bg-primary" href="javascript:void(0);">
                                +8
                            </a>
                        </div>
                        <div class="avatar-list-stacked mb-3">
                            <span class="avatar">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="avatar">
                            </span>
                            <a class="avatar bg-primary" href="javascript:void(0);">
                                +8
                            </a>
                        </div>
                        <div class="avatar-list-stacked avatar-group-sm">
                            <span class="avatar">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" alt="avatar">
                            </span>
                            <a class="avatar bg-primary" href="javascript:void(0);">
                                +8
                            </a>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Avatar Group - Rounded</h5>
                    </div>
                    <div class="card-body">
                        <div class="avatar-list-stacked avatar-group-lg mb-3">
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="avatar">
                            </span>
                            <a class="avatar bg-primary avatar-rounded" href="javascript:void(0);">
                                +8
                            </a>
                        </div>
                        <div class="avatar-list-stacked mb-3">
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="avatar">
                            </span>
                            <a class="avatar bg-primary avatar-rounded" href="javascript:void(0);">
                                +8
                            </a>
                        </div>
                        <div class="avatar-list-stacked avatar-group-sm">
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="avatar">
                            </span>
                            <span class="avatar avatar-rounded">
                                <img class="border border-white" src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" alt="avatar">
                            </span>
                            <a class="avatar bg-primary avatar-rounded" href="javascript:void(0);">
                                +8
                            </a>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->

        </div>
        <!-- end row -->
    </div>
</div>
@endsection
