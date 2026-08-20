<?php $page = 'activities'; ?>
@extends('layout.mainlayout')
@section('content')

<div class="page-wrapper">
    <div class="content">
        <!-- Activities -->
        <div class="card">
            <div class="card-header pb-1">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div class="mb-3">
                        <h4>Notifications</h4>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <a href="javascript:void(0);" class="btn btn-light me-2"><i class="ti ti-check me-2"></i>Mark all as read</a>
                        <a href="javascript:void(0);" class="btn btn-danger"><i class="ti ti-trash me-2"></i>Delete all</a>
                    </div>
                </div>
            </div>
            <div class="card-body pb-1">
                <div class="d-block">
                    <div class="d-flex align-items-center justify-content-between flex-wrap shadow-sm p-3 pb-0 noti-hover border rounded mb-3"> 
                        <div class="d-flex align-items-start flex-fill">
                            <a href="#" class="avatar avatar-lg flex-shrink-0 me-2 mb-2">
                                <img alt="Lesley Grauer" src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}" class=" img-fluid">
                            </a>
                            <div class="mb-3">
                                <p class="mb-0 text-dark  fw-medium">Sylvia added appointment on 02:00 PM</p>
                                <span>4 mins ago</span>
                                <div class="d-flex align-items-center mt-1">
                                    <a href="javascript:void(0);" class="btn btn-light btn-sm me-2">Decline</a>
                                    <a href="javascript:void(0);" class="btn btn-primary btn-sm">Accept</a>
                                </div>
                            </div>
                        </div>
                        <div class="noti-delete mb-3">
                            <a href="javascript:void(0);" class="btn btn-danger btn-sm text-white">Delete</a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap shadow-sm noti-hover border p-3 pb-0 rounded mb-3">
                        <div class="d-flex align-items-start flex-fill">
                            <a href="#" class="avatar avatar-lg flex-shrink-0 me-2 mb-3">
                                <img alt="Lesley Grauer" src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" class=" img-fluid">
                            </a>
                            <div class="mb-3">
                                <p class="mb-0 text-dark  fw-medium">Shawn performance in Math is below the threshold.</p>
                                <span>6 mins ago</span>
                            </div>
                        </div>
                        <div class="noti-delete mb-3">
                            <a href="javascript:void(0);" class="btn btn-danger btn-sm text-white">Delete</a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap shadow-sm noti-hover border p-3 pb-0 rounded mb-3">
                        <div class="d-flex align-items-start flex-fill">
                            <a href="#" class="avatar avatar-lg flex-shrink-0 me-2 mb-3">
                                <img alt="Lesley Grauer" src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" class=" img-fluid">
                            </a>
                            <div class="mb-3">
                                <p class="mb-0 text-dark fw-medium">New student record George is created by Teressa</p>
                                <span>2 hrs ago</span>
                            </div>
                        </div>
                        <div class="noti-delete mb-3">
                            <a href="javascript:void(0);" class="btn btn-danger btn-sm text-white">Delete</a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap shadow-sm noti-hover border p-3 pb-0 rounded mb-3">
                        <div class="d-flex align-items-start flex-fill">
                            <a href="#" class="avatar avatar-lg flex-shrink-0 me-2 mb-3">
                                <img alt="Lesley Grauer" src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}" class=" img-fluid">
                            </a>
                            <div class="mb-3">
                                <p class="mb-0 text-dark  fw-medium">A new teacher record for John</p>
                                <span>09:45 AM</span>
                            </div>
                        </div>
                        <div class="noti-delete mb-3">
                            <a href="javascript:void(0);" class="btn btn-danger btn-sm text-white">Delete</a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between flex-wrap shadow-sm noti-hover border p-3 pb-0 rounded mb-3">
                        <div class="d-flex align-items-start flex-fill">
                            <a href="#" class="avatar avatar-lg flex-shrink-0 me-2 mb-3">
                                <img alt="Lesley Grauer" src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}" class=" img-fluid">
                            </a>
                            <div class="mb-3">
                                <p class="mb-0 text-dark  fw-medium">A new teacher record for Ellisa</p>
                                <span>10 mins ago</span>
                            </div>
                        </div>
                        <div class="noti-delete mb-3">
                            <a href="javascript:void(0);" class="btn btn-danger btn-sm text-white">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Activities -->
    </div>
</div>


@endsection