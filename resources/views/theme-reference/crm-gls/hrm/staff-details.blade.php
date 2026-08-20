<?php $page = 'staff-details'; ?>
@extends('layout.mainlayout')
@section('content')

<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        <div class="row">
            <!-- Page Header -->
            <div class="col-md-12">
                <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
                    <div class="my-auto mb-2">
                        <h3 class="page-title mb-1">Staff Details</h3>
                        <nav>
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="{{url('index')}}">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{url('students')}}">HRM</a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">Staff Details</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex my-xl-auto right-content align-items-center  flex-wrap">
                        <a href="{{url('edit-staff')}}" class="btn btn-primary d-flex align-items-center mb-2"><i
                                class="ti ti-edit-circle me-2"></i>Edit Staff</a>
                    </div>
                </div>

            </div>
            <!-- /Page Header -->
            <div class="col-xxl-3 col-lg-4 theiaStickySidebar">
                <div class="card border-white">
                    <div class="card-header">
                        <div class="d-flex align-items-center  row-gap-3">
                            <div
                                class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                <img src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}" class="img-fluid" alt="img">
                            </div>
                            <div>
                                <span class="badge badge-soft-success d-inline-flex align-items-center mb-1"><i
                                        class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                <h5 class="mb-1">Kevin Larry</h5>
                                <p class="text-primary m-0">AD1256589</p>
                                <p class="p-0">Joined On : 10 Mar 2024</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-3">Basic Information</h5>
                        <dl class="row mb-0">
                            <dt class="col-6 fw-normal mb-3">Staff ID</dt>
                            <dd class="col-6 text-dark mb-3">35013</dd>
                            <dt class="col-6 fw-normal mb-3">Gender</dt>
                            <dd class="col-6 text-dark mb-3">Male</dd>
                            <dt class="col-6 fw-normal mb-3">Designation</dt>
                            <dd class="col-6 text-dark mb-3">25 Jan 2008</dd>
                            <dt class="col-6 fw-normal mb-3">Department</dt>
                            <dd class="col-6 text-dark mb-3">Technical Lead</dd>
                            <dt class="col-6 fw-normal mb-3">Date Of Birth</dt>
                            <dd class="col-6 text-dark mb-3">Admin</dd>
                            <dt class="col-6 fw-normal mb-3">Blood Group</dt>
                            <dd class="col-6 text-dark mb-3">15 Aug 1987</dd>
                            <dt class="col-6 fw-normal mb-3">Blood Group</dt>
                            <dd class="col-6 text-dark mb-3">O+</dd>
                            <dt class="col-6 fw-normal mb-3">Mother tongue</dt>
                            <dd class="col-6 text-dark mb-3">English</dd>
                            <dt class="col-6 fw-normal mb-0">Language</dt>
                            <dd class="col-6 text-dark mb-0"><span
                                    class="badge badge-light text-dark me-2">English</span><span
                                    class="badge badge-light text-dark">Spanish</span></dd>
                        </dl>
                    </div>
                </div>
                <div class="card border-white">
                    <div class="card-body">
                        <h5 class="mb-3">Primary Contact Info</h5>
                        <div class="d-flex align-items-center mb-3">
                            <span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-phone"></i></span>
                            <div>
                                <span class="fs-12 mb-1">Phone Number</span>
                                <p class="text-dark">+1 46548 84498</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-mail"></i></span>
                            <div>
                                <span class="fs-12 mb-1">Email Address</span>
                                <p class="text-dark">jan@example.com</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-xxl-9 col-lg-8">
                <div class="row">
                    <div class="col-md-12">
                        <ul class="nav nav-tabs nav-tabs-bottom mb-4">
                            <li>
                                <a href="{{url('staff-details')}}" class="nav-link active"><i
                                        class="ti ti-info-square-rounded me-2"></i>Basic Details</a>
                            </li>
                            <li>
                                <a href="{{url('staff-payroll')}}" class="nav-link"><i class="ti ti-file-dollar me-2"></i>Payroll</a>
                            </li>
                            <li>
                                <a href="{{url('staff-leaves')}}" class="nav-link"><i class="ti ti-calendar-due me-2"></i>Leaves</a>
                            </li>
                            <li>
                                <a href="{{url('staffs-attendance')}}" class="nav-link"><i
                                        class="ti ti-calendar-due me-2"></i>Attendance</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="row">
                    <!-- Address -->
                    <div class="col-xxl-6 d-flex">
                        <div class="card flex-fill">
                            <div class="card-header">
                                <h5>Address</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <span
                                        class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
                                            class="ti ti-map-pin-up"></i></span>
                                    <div>
                                        <p class="mb-1">Current Address</p>
                                        <p class="text-dark">3495 Red Hawk Road, Buffalo Lake, MN 55314</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span
                                        class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
                                            class="ti ti-map-pins"></i></span>
                                    <div>
                                        <p class="mb-1">Permanent Address</p>
                                        <p class="text-dark">3495 Red Hawk Road, Buffalo Lake, MN 55314</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Address -->

                    <!-- Documents -->
                    <div class="col-xxl-6 d-flex">
                        <div class="card flex-fill">
                            <div class="card-header">
                                <h5>Documents</h5>
                            </div>
                            <div class="card-body">
                                <div
                                    class="bg-light-300 border rounded d-flex align-items-center justify-content-between mb-3 p-2">
                                    <div class="d-flex align-items-center overflow-hidden">
                                        <span
                                            class="avatar avatar-md bg-white rounded flex-shrink-0 text-default"><i
                                                class="ti ti-pdf fs-15"></i></span>
                                        <div class="ms-2">
                                            <p class="text-truncate">Resume.pdf</p>
                                        </div>
                                    </div>
                                    <a href="#" class="btn btn-dark btn-icon btn-sm"><i
                                            class="ti ti-download"></i></a>
                                </div>
                                <div
                                    class="bg-light-300 border rounded d-flex align-items-center justify-content-between p-2">
                                    <div class="d-flex align-items-center overflow-hidden">
                                        <span
                                            class="avatar avatar-md bg-white rounded flex-shrink-0 text-default"><i
                                                class="ti ti-pdf fs-15"></i></span>
                                        <div class="ms-2">
                                            <p class="text-truncate">Joining Letter.pdf</p>
                                        </div>
                                    </div>
                                    <a href="#" class="btn btn-dark btn-icon btn-sm"><i
                                            class="ti ti-download"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Documents -->

                    <!-- Bank Details -->
                    <div class="col-xxl-12 d-flex">
                        <div class="card flex-fill">
                            <div class="card-header">
                                <h5>Bank Details</h5>
                            </div>
                            <div class="card-body pb-1">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <p class="mb-1">Account Name</p>
                                            <p class="text-dark">Bank of America</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <p class="mb-1">Account Number</p>
                                            <p class="text-dark">178849035684</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <p class="mb-1">Bank Name</p>
                                            <p class="text-dark">Bank of America</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <p class="mb-1">Branch</p>
                                            <p class="text-dark">Cincinnati</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <p class="mb-1">IFSC</p>
                                            <p class="text-dark">BOA83209832</p>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Bank Details -->

                    <!-- Other Info -->
                    <div class="col-xxl-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Other Info</h5>
                            </div>
                            <div class="card-body">
                                <p>Depending on the specific needs of your organization or system, additional
                                    information may be collected or tracked. It's important to ensure that any
                                    data
                                    collected complies with privacy regulations and policies to protect
                                    students'
                                    sensitive information.</p>
                            </div>
                        </div>
                    </div>
                    <!-- /Other Info -->
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Page Wrapper -->	
@component('components.modal-popup')        
@endcomponent
@endsection