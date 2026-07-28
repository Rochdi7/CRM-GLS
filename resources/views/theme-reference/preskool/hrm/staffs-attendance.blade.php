<?php $page = 'staffs-attendance'; ?>
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

        </div>

        <div class="row">
            <!-- Staff Information -->
            <div class="col-xxl-3 col-lg-4 theiaStickySidebar">
                <div class="card border-white">
                    <div class="card-header">
                        <div class="d-flex align-items-center row-gap-3">
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
                            <span
                                class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
                                    class="ti ti-phone"></i></span>
                            <div>
                                <span class="fs-12 mb-1">Phone Number</span>
                                <p class="text-dark">+1 46548 84498</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <span
                                class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i
                                    class="ti ti-mail"></i></span>
                            <div>
                                <span class="fs-12 mb-1">Email Address</span>
                                <p class="text-dark">jan@example.com</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /Staff Information -->

            <div class="col-xxl-9 col-xl-8">
                <div class="row">
                    <div class="col-md-12">

                        <!-- List -->
                        <ul class="nav nav-tabs nav-tabs-bottom mb-4">
                            <li>
                                <a href="{{url('staff-details')}}" class="nav-link "><i
                                        class="ti ti-info-square-rounded me-2"></i>Basic Details</a>
                            </li>
                            <li>
                                <a href="{{url('staff-payroll')}}" class="nav-link "><i
                                        class="ti ti-file-dollar me-2"></i>Payroll</a>
                            </li>
                            <li>
                                <a href="{{url('staff-leaves')}}" class="nav-link"><i
                                        class="ti ti-calendar-due me-2"></i>Leaves</a>
                            </li>
                            <li>
                                <a href="{{url('staffs-attendance')}}" class="nav-link active"><i
                                        class="ti ti-calendar-due me-2"></i>Attendance</a>
                            </li>
                        </ul>
                        <!-- /List -->

                        <!-- Attendance -->
                        <div class="card">
                            <div
                                class="card-header d-flex align-items-center justify-content-between flex-wrap pb-1">
                                <h4 class="mb-3">Attendance</h4>
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="d-flex align-items-center flex-wrap me-3">
                                        <p class="text-dark mb-3 me-2">Last Updated on : 25 May 2024
                                        </p>
                                        <a href="#"
                                            class="btn btn-primary btn-icon btn-sm rounded-circle d-inline-flex align-items-center justify-content-center p-0 mb-3"><i
                                                class="ti ti-refresh-dot"></i></a>
                                    </div>
                                    <div class="dropdown mb-3">
                                        <a href="javascript:void(0);"
                                            class="btn btn-outline-light bg-white dropdown-toggle"
                                            data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                                class="ti ti-calendar-due me-2"></i>Year : 2024 /
                                            2025</a>
                                        <ul class="dropdown-menu p-3">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                                    Year : 2024 / 2025
                                                </a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                                    Year : 2023 / 2024
                                                </a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                                    Year : 2022 / 2023
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body pb-1">
                                <div class="row">
                                    <!-- Total Present -->
                                    <div class="col-md-6 col-xxl-3 d-flex">
                                        <div
                                            class="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                            <span
                                                class="avatar avatar-lg bg-primary-transparent rounded me-2 flex-shrink-0 text-primary"><i
                                                    class="ti ti-user-check fs-24"></i></span>
                                            <div class="ms-2">
                                                <p class="mb-1">Present</p>
                                                <h5>265</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /Total Present -->

                                    <!-- Total Absent -->
                                    <div class="col-md-6 col-xxl-3 d-flex">
                                        <div
                                            class="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                            <span
                                                class="avatar avatar-lg bg-danger-transparent rounded me-2 flex-shrink-0 text-danger"><i
                                                    class="ti ti-user-check fs-24"></i></span>
                                            <div class="ms-2">
                                                <p class="mb-1">Absent</p>
                                                <h5>05</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /Total Absent -->

                                    <!-- Half Day -->
                                    <div class="col-md-6 col-xxl-3 d-flex">
                                        <div
                                            class="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                            <span
                                                class="avatar avatar-lg bg-info-transparent rounded me-2 flex-shrink-0 text-info"><i
                                                    class="ti ti-user-check fs-24"></i></span>
                                            <div class="ms-2">
                                                <p class="mb-1">Half Day</p>
                                                <h5>01</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /Half Day -->

                                    <!-- Late to School-->
                                    <div class="col-md-6 col-xxl-3 d-flex">
                                        <div
                                            class="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                            <span
                                                class="avatar avatar-lg bg-warning-transparent rounded me-2 flex-shrink-0 text-warning"><i
                                                    class="ti ti-user-check fs-24"></i></span>
                                            <div class="ms-2">
                                                <p class="mb-1">Late</p>
                                                <h5>12</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- /Late to School-->

                                </div>
                            </div>
                        </div>
                        <div class="card">
                            <div
                                class="card-header d-flex align-items-center justify-content-between flex-wrap pb-1">
                                <h4 class="mb-3">Attendance</h4>
                                <div class="d-flex align-items-center flex-wrap">
                                    <div class="dropdown mb-3 me-3">
                                        <a href="javascript:void(0);"
                                            class="btn btn-outline-light border-white bg-white dropdown-toggle shadow-md"
                                            data-bs-toggle="dropdown"><i
                                                class="ti ti-calendar-due me-2"></i>This Year</a>
                                        <ul class="dropdown-menu p-3">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                                    This Year
                                                </a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                                    This Month
                                                </a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                                    This Week
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="dropdown mb-3">
                                        <a href="javascript:void(0);"
                                            class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center"
                                            data-bs-toggle="dropdown">
                                            <i class="ti ti-file-export me-2"></i>Export
                                        </a>
                                        <ul class="dropdown-menu  dropdown-menu-end p-3">
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                                        class="ti ti-file-type-pdf me-2"></i>Export as
                                                    PDF</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);" class="dropdown-item rounded-1"><i
                                                        class="ti ti-file-type-xls me-2"></i>Export as
                                                    Excel </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0 py-3">

                                <div class="px-3">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <div
                                            class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                            <span
                                                class="avatar avatar-sm bg-success rounded me-2 flex-shrink-0 "><i
                                                    class="ti ti-checks"></i></span>
                                            <p class="text-dark">Present</p>
                                        </div>
                                        <div
                                            class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                            <span
                                                class="avatar avatar-sm bg-danger rounded me-2 flex-shrink-0 "><i
                                                    class="ti ti-x"></i></span>
                                            <p class="text-dark">Absent</p>
                                        </div>
                                        <div
                                            class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                            <span
                                                class="avatar avatar-sm bg-pending rounded me-2 flex-shrink-0 "><i
                                                    class="ti ti-clock-x"></i></span>
                                            <p class="text-dark">Late</p>
                                        </div>
                                        <div
                                            class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                            <span
                                                class="avatar avatar-sm bg-dark rounded me-2 flex-shrink-0 "><i
                                                    class="ti ti-calendar-event"></i></span>
                                            <p class="text-dark">Halfday</p>
                                        </div>
                                        <div
                                            class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                            <span
                                                class="avatar avatar-sm bg-info rounded me-2 flex-shrink-0 "><i
                                                    class="ti ti-calendar-event"></i></span>
                                            <p class="text-dark">Holiday</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Attendance List -->
                                <div class="custom-datatable-filter table-responsive">
                                    <table class="table datatable">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Date <span class="divide-bar"> | </span> Month</th>
                                                <th>Jun</th>
                                                <th>Jul</th>
                                                <th>Aug</th>
                                                <th>Sep</th>
                                                <th>Oct</th>
                                                <th>Nov</th>
                                                <th>Dec</th>
                                                <th>Jan</th>
                                                <th>Feb</th>
                                                <th>Mar</th>
                                                <th>Apr</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>01</td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span></span></td>
                                                <td><span></span></td>
                                            </tr>
                                            <tr>
                                                <td>02</td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span></span></td>
                                                <td><span></span></td>
                                            </tr>
                                            <tr>
                                                <td>03</td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-danger"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-danger"></span>
                                                </td>
                                                <td><span></span></td>
                                                <td><span></span></td>
                                            </tr>
                                            <tr>
                                                <td>04</td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span></span></td>
                                                <td><span></span></td>
                                            </tr>
                                            <tr>
                                                <td>05</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td>06</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td>07</td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span></span></td>
                                                <td><span></span></td>
                                            </tr>
                                            <tr>
                                                <td>08</td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span></span></td>
                                                <td><span></span></td>
                                            </tr>
                                            <tr>
                                                <td>09</td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-danger"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-pending"></span>
                                                </td>
                                                <td><span class="attendance-range bg-danger"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-dark"></span></td>
                                                <td><span></span></td>
                                                <td><span></span></td>
                                            </tr>
                                            <tr>
                                                <td>10</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td>11</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td>12</td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span></span></td>
                                                <td><span></span></td>
                                            </tr>
                                            <tr>
                                                <td>13</td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-pending"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-dark"></span></td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span class="attendance-range bg-success"></span>
                                                </td>
                                                <td><span></span></td>
                                                <td><span></span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- /Attendance List -->
                            </div>
                        </div>
                        <!-- /Attendance -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@component('components.modal-popup')        
@endcomponent
@endsection