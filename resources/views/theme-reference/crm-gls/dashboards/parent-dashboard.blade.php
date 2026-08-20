<?php $page ='parent-dashboard'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Parent Dashboard</h3>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Parent Dashboard</li>
                    </ol>
                </nav>
            </div>
            <div class="dash-select-student d-flex align-items-center mb-2">
                <h6 class="mb-0">Select Student</h6>
                <div class="student-active d-flex align-items-center ms-2">
                    <a href="#" class="avatar avatar-lg p-1 me-2 active">
                        <img src="{{URL::asset('build/img/students/student-01.jpg')}}" alt="Profile">
                    </a>
                    <a href="#" class="avatar avatar-lg p-1">
                        <img src="{{URL::asset('build/img/students/student-02.jpg')}}" alt="Profile">
                    </a>
                </div>
            </div>
        </div>
        <!-- /Page Header -->

        <div class="row">

            <!-- Profile -->
            <div class="col-xxl-5 col-xl-12 d-flex">
                <div class="card bg-dark position-relative flex-fill">
                    <div class="card-body">
                        <div class="d-flex align-items-center row-gap-3">
                            <div class="avatar avatar-xxl rounded flex-shrink-0 me-3">
                                <img src="{{URL::asset('build/img/parents/parent-01.jpg')}}" alt="Img">
                            </div>
                            <div class="d-block">
                                <span class="badge bg-transparent-primary text-primary mb-1">#P124556</span>
                                <h4 class="text-truncate text-white mb-1">Thomas Bown</h4>
                                <div class="d-flex align-items-center flex-wrap row-gap-2 class-info">
                                    <span>Added On : 25 Mar 2024</span>
                                    <span>Child : Janet</span>
                                </div>
                            </div>
                        </div>
                        <div class="student-card-bg">
                            <img src="{{URL::asset('build/img/bg/circle-shape.png')}}" alt="Bg">
                            <img src="{{URL::asset('build/img/bg/shape-02.png')}}" alt="Bg">
                            <img src="{{URL::asset('build/img/bg/shape-04.png')}}" alt="Bg">
                            <img src="{{URL::asset('build/img/bg/blue-polygon.png')}}" alt="Bg">
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Profile -->

            <!-- Leave -->
            <div class="col-xxl-7 d-flex">
                <div class="row flex-fill">
                    <div class="col-xl-4 d-flex flex-column">
                        <div
                            class="d-flex bg-white border rounded flex-wrap justify-content-between align-items-center p-3 row-gap-2 mb-2 animate-card">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-sm bg-light-500 me-2 rounded"><i
                                        class="ti ti-calendar-event text-dark fs-16"></i></span>
                                <h6>Apply Leave</h6>
                            </div>
                            <a href="{{url('student-leaves')}}"
                                class="badge rounded-circle arrow d-flex align-items-center justify-content-center"><i
                                    class="ti ti-chevron-right fs-14"></i></a>
                        </div>
                        <div
                            class="d-flex bg-white border rounded flex-wrap justify-content-between align-items-center p-3 row-gap-2 mb-4 animate-card">
                            <div class="d-flex align-items-center">
                                <span class="avatar avatar-sm bg-light-500 me-2 rounded"><i
                                        class="ti ti-message-up text-dark fs-16"></i></span>
                                <h6>Raise a Request</h6>
                            </div>
                            <a href="{{url('approve-request')}}"
                                class="badge rounded-circle arrow d-flex align-items-center justify-content-center"><i
                                    class="ti ti-chevron-right fs-14"></i></a>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card bg-success-transparent border-3 border-white text-center p-3">
                            <span class="avatar avatar-sm rounded bg-success mx-auto mb-3">
                                <i class="ti ti-calendar-share fs-15"></i>
                            </span>
                            <h6 class="mb-2">Medical Leaves (10)</h6>
                            <div class="d-flex align-items-center justify-content-between text-default">
                                <p class="border-end mb-0">Used : 05</p>
                                <p>Available : 10</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <div class="card bg-primary-transparent border-3 border-white text-center p-3">
                            <span class="avatar avatar-sm rounded bg-primary mx-auto mb-3">
                                <i class="ti ti-hexagonal-prism-plus fs-15"></i>
                            </span>
                            <h6 class="mb-2">Casual Leaves (12)</h6>
                            <div class="d-flex align-items-center justify-content-between text-default">
                                <p class="border-end mb-0">Used : 05</p>
                                <p>Available : 10</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Leave -->

        </div>
        <div class="row">

            <!-- Events List -->
            <div class="col-xxl-4 col-xl-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header  d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Events List</h4>
                        <a href="{{url('events')}}" class="fw-medium">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);"
                                            class="avatar avatar-lg flex-shrink-0 me-2">
                                            <img src="{{URL::asset('build/img/events/event-01.jpg')}}" class="img-fluid"
                                                alt="img">
                                        </a>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1"><a href="{{url('events')}}">Parents, Teacher Meet</a>
                                            </h6>
                                            <p><i class="ti ti-calendar me-1"></i>15 July 2024</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Full Day</span>
                                </div>
                            </li>
                            <li class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);"
                                            class="avatar avatar-lg flex-shrink-0 me-2">
                                            <img src="{{URL::asset('build/img/events/event-02.jpg')}}" class="img-fluid"
                                                alt="img">
                                        </a>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1"><a href="{{url('events')}}">Farewell</a></h6>
                                            <p><i class="ti ti-calendar me-1"></i>11 Mar 2024</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-skyblue d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Half Day</span>
                                </div>
                            </li>
                            <li class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);"
                                            class="avatar avatar-lg flex-shrink-0 me-2">
                                            <img src="{{URL::asset('build/img/events/event-03.jpg')}}" class="img-fluid"
                                                alt="img">
                                        </a>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1"><a href="{{url('events')}}">Annual Day</a></h6>
                                            <p><i class="ti ti-calendar me-1"></i>11 Mar 2024</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-skyblue d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Half Day</span>
                                </div>
                            </li>
                            <li class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);"
                                            class="avatar avatar-lg flex-shrink-0 me-2">
                                            <img src="{{URL::asset('build/img/events/event-04.jpg')}}" class="img-fluid"
                                                alt="img">
                                        </a>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1"><a href="{{url('events')}}">Holi Celebration</a></h6>
                                            <p><i class="ti ti-calendar me-1"></i>15 July 2024</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Full Day</span>
                                </div>
                            </li>
                            <li class="list-group-item p-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);"
                                            class="avatar avatar-lg flex-shrink-0 me-2">
                                            <img src="{{URL::asset('build/img/events/event-05.jpg')}}" class="img-fluid"
                                                alt="img">
                                        </a>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1"><a href="{{url('events')}}">Exam Result</a></h6>
                                            <p><i class="ti ti-calendar me-1"></i>16 July 2024</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-skyblue d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Half Day</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /Events List -->

            <!-- Statistics -->
            <div class="col-xxl-8 col-xl-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Statistics</h4>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                data-bs-toggle="dropdown"><i class="ti ti-calendar me-2"></i>This Month
                            </a>
                            <ul class="dropdown-menu mt-2 p-3">
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        This Month
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        This Year
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        Last Week
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body pb-0">
                        <div id="statistic_chart"></div>
                    </div>
                </div>
            </div>
            <!-- /Statistics -->

        </div>
        <div class="row">

            <!-- Leave Status -->
            <div class="col-xxl-4 col-xl-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Leave Status</h4>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                data-bs-toggle="dropdown"><i class="ti ti-calendar me-2"></i>This Month
                            </a>
                            <ul class="dropdown-menu mt-2 p-3">
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        This Month
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        This Year
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        Last Week
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-3">
                            <div class="d-flex align-items-center mb-2 mb-sm-0">
                                <div class="avatar avatar-lg bg-danger-transparent flex-shrink-0 me-2"><i
                                        class="ti ti-brand-socket-io"></i></div>
                                <div>
                                    <h6 class="mb-1">Emergency Leave</h6>
                                    <p>Date : 15 Jun 2024</p>
                                </div>
                            </div>
                            <span class="badge bg-skyblue d-inline-flex align-items-center"><i
                                    class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                        </div>
                        <div class="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-3">
                            <div class="d-flex align-items-center mb-2 mb-sm-0">
                                <div class="avatar avatar-lg bg-info-transparent flex-shrink-0 me-2"><i
                                        class="ti ti-medical-cross"></i></div>
                                <div>
                                    <h6 class="mb-1">Medical Leave</h6>
                                    <p>Date : 15 Jun 2024</p>
                                </div>
                            </div>
                            <span class="badge bg-success d-inline-flex align-items-center"><i
                                    class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                        </div>
                        <div class="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-3">
                            <div class="d-flex align-items-center mb-2 mb-sm-0">
                                <div class="avatar avatar-lg bg-info-transparent flex-shrink-0 me-2"><i
                                        class="ti ti-medical-cross"></i></div>
                                <div>
                                    <h6 class="mb-1">Medical Leave</h6>
                                    <p>Date : 16 Jun 2024</p>
                                </div>
                            </div>
                            <span class="badge bg-danger d-inline-flex align-items-center"><i
                                    class="ti ti-circle-filled fs-5 me-1"></i>Declined</span>
                        </div>
                        <div class="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-0">
                            <div class="d-flex align-items-center mb-2 mb-sm-0">
                                <div class="avatar avatar-lg bg-danger-transparent flex-shrink-0 me-2"><i
                                        class="ti ti-brand-socket-io"></i></div>
                                <div>
                                    <h6 class="mb-1">Fever</h6>
                                    <p>Date : 16 Jun 2024</p>
                                </div>
                            </div>
                            <span class="badge bg-success d-inline-flex align-items-center"><i
                                    class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Leave Status -->

            <!-- Home Works -->
            <div class="col-xxl-4  col-xl-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-titile">Home Works</h4>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                data-bs-toggle="dropdown"><i class="ti ti-book-2 me-2"></i>All Subject
                            </a>
                            <ul class="dropdown-menu mt-2 p-3">
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        Physics
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        Chemistry
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        Maths
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body py-1">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item py-3 px-0">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-xl flex-shrink-0 me-2">
                                        <img src="{{URL::asset('build/img/home-work/home-work-01.jpg')}}" alt="img">
                                    </a>
                                    <div class="overflow-hidden">
                                        <p class="d-flex align-items-center text-info mb-1"><i
                                                class="ti ti-tag me-2"></i>Physics</p>
                                        <h6 class="text-truncate mb-1"><a href="{{url('class-home-work')}}">Write
                                                about Theory of Pendulum</a></h6>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <div class="d-flex align-items-center border-end me-1 pe-1">
                                                <a href="{{url('teacher-details')}}"
                                                    class="avatar avatar-xs flex-shrink-0 me-2">
                                                    <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
                                                        class="rounded-circle" alt="teacher">
                                                </a>
                                                <p class="text-dark">Aaron</p>
                                            </div>
                                            <p>Due by : 16 Jun 2024</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-xl flex-shrink-0 me-2">
                                        <img src="{{URL::asset('build/img/home-work/home-work-02.jpg')}}" alt="img">
                                    </a>
                                    <div class="overflow-hidden">
                                        <p class="d-flex align-items-center text-success mb-1"><i
                                                class="ti ti-tag me-2"></i>Chemistry</p>
                                        <h6 class="text-truncate mb-1"><a href="{{url('class-home-work')}}">Chemistry
                                                - Change of Elements</a></h6>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <div class="d-flex align-items-center border-end me-1 pe-1">
                                                <a href="{{url('teacher-details')}}"
                                                    class="avatar avatar-xs flex-shrink-0 me-2">
                                                    <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
                                                        class="rounded-circle" alt="teacher">
                                                </a>
                                                <p class="text-dark">Hellana</p>
                                            </div>
                                            <p>Due by : 16 Jun 2024</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-xl flex-shrink-0 me-2">
                                        <img src="{{URL::asset('build/img/home-work/home-work-03.jpg')}}" alt="img">
                                    </a>
                                    <div class="overflow-hidden">
                                        <p class="d-flex align-items-center text-danger mb-1"><i
                                                class="ti ti-tag me-2"></i>Maths</p>
                                        <h6 class="text-truncate mb-1"><a href="{{url('class-home-work')}}">Maths -
                                                Problems to Solve Page 21</a></h6>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <div class="d-flex align-items-center border-end me-1 pe-1">
                                                <a href="{{url('teacher-details')}}"
                                                    class="avatar avatar-xs flex-shrink-0 me-2">
                                                    <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
                                                        class="rounded-circle" alt="teacher">
                                                </a>
                                                <p class="text-dark">Morgan</p>
                                            </div>
                                            <p>Due by : 21 Jun 2024</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0">
                                <div class="d-flex align-items-center">
                                    <a href="javascript:void(0);" class="avatar avatar-xl flex-shrink-0 me-2">
                                        <img src="{{URL::asset('build/img/home-work/home-work-04.jpg')}}" alt="img">
                                    </a>
                                    <div class="overflow-hidden">
                                        <p class="d-flex align-items-center text-skyblue mb-1"><i
                                                class="ti ti-tag me-2"></i>Engish</p>
                                        <h6 class="text-truncate mb-1"><a href="{{url('class-home-work')}}">English -
                                                Vocabulary Introduction</a></h6>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <div class="d-flex align-items-center border-end me-1 pe-1">
                                                <a href="{{url('teacher-details')}}"
                                                    class="avatar avatar-xs flex-shrink-0 me-2">
                                                    <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
                                                        class="rounded-circle" alt="teacher">
                                                </a>
                                                <p class="text-dark">Daniel Josua</p>
                                            </div>
                                            <p>Due by : 21 Jun 2024</p>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /Home Works -->

            <!-- Fees Reminder -->
            <div class="col-xxl-4 col-xl-12 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-titile">Fees Reminder</h4>
                        <a href="{{url('fees-assign')}}" class="link-primary fw-medium">View All</a>
                    </div>
                    <div class="card-body py-1">
                        <div class="d-flex align-items-center justify-content-between py-3">
                            <div class="d-flex align-items-center overflow-hidden me-2">
                                <span
                                    class="bg-info-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                                    <i class="ti ti-bus-stop fs-16"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <h6 class="text-truncate mb-1">Transport Fees</h6>
                                    <p>$2500</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-1">Last Date</h6>
                                <p>25 May 2024</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-3">
                            <div class="d-flex align-items-center overflow-hidden me-2">
                                <span
                                    class="bg-success-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                                    <i class="ti ti-books fs-16"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <h6 class="text-truncate mb-1">Book Fees</h6>
                                    <p>$2500</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-1">Last Date</h6>
                                <p>25 May 2024</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-3">
                            <div class="d-flex align-items-center overflow-hidden me-2">
                                <span
                                    class="bg-info-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                                    <i class="ti ti-report-money fs-16"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <h6 class="text-truncate mb-1">Exam Fees</h6>
                                    <p>$2500</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-1">Last Date</h6>
                                <p>25 May 2024</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-3">
                            <div class="d-flex align-items-center overflow-hidden me-2">
                                <span
                                    class="bg-skyblue-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                                    <i class="ti ti-meat fs-16"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <h6 class="text-truncate mb-1">Mess Fees <span
                                            class="d-inline-flex align-items-center badge badge-soft-danger"><i
                                                class="ti ti-circle-filled me-1 fs-5"></i>Due</span></h6>
                                    <p class="text-danger">$2500 + $150</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <a href="#" class="btn btn-primary btn-sm">Pay now</a>
                            </div>
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-3">
                            <div class="d-flex align-items-center overflow-hidden me-2">
                                <span
                                    class="bg-danger-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                                    <i class="ti ti-report-money fs-16"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <h6 class="text-truncate mb-1">Hostel</h6>
                                    <p>$2500</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-1">Last Date</h6>
                                <p>25 May 2024</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Fees Reminder -->

        </div>

        <div class="row">

            <!-- Exam Result -->
            <div class="col-xxl-8 col-xl-7 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                        <h4 class="card-title mb-3">Exam Result</h4>
                        <div class="d-flex align-items-center">
                            <div class="dropdown me-3 mb-3">
                                <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                    data-bs-toggle="dropdown"><i class="ti ti-calendar me-2"></i>All Classes
                                </a>
                                <ul class="dropdown-menu mt-2 p-3">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            I
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            II
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            III
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="dropdown mb-3">
                                <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                    data-bs-toggle="dropdown"><i class="ti ti-calendar me-2"></i>All Exams
                                </a>
                                <ul class="dropdown-menu mt-2 p-3">
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Quartely
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            Practical
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                            1st Term
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0">
                        <div class="custom-datatable-filter table-responsive">
                            <table class="table">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Class </th>
                                        <th>Section</th>
                                        <th>Marks %</th>
                                        <th>Exams</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>35013</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-01.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a
                                                            href="{{url('student-details')}}">Janet</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>III</td>
                                        <td>A</td>
                                        <td>89%</td>
                                        <td>Quartely</td>
                                        <td>
                                            <span class="badge bg-success">Pass</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>35013</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-02.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a
                                                            href="{{url('staff-details')}}">Joann</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>IV</td>
                                        <td>B</td>
                                        <td>88%</td>
                                        <td>Practical</td>
                                        <td>
                                            <span class="badge bg-success">Pass</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>35010</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-04.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a
                                                            href="{{url('student-details')}}">Gifford</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>I</td>
                                        <td>B</td>
                                        <td>21%</td>
                                        <td>Mid Term</td>
                                        <td>
                                            <span class="badge bg-success">Pass</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>35009</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-05.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a
                                                            href="{{url('student-details')}}">Lisa</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>II</td>
                                        <td>B</td>
                                        <td>31%</td>
                                        <td>Annual</td>
                                        <td>
                                            <span class="badge bg-danger">Fail</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>35015</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-08.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a
                                                            href="{{url('student-details')}}">Riana</a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>III</td>
                                        <td>A</td>
                                        <td>89%</td>
                                        <td>Quartely</td>
                                        <td>
                                            <span class="badge bg-success">Pass</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>35013</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <a href="{{url('student-details')}}" class="avatar avatar-md"><img
                                                        src="{{URL::asset('build/img/students/student-06.jpg')}}"
                                                        class="img-fluid rounded-circle" alt="img"></a>
                                                <div class="ms-2">
                                                    <p class="text-dark mb-0"><a
                                                            href="{{url('staff-details')}}">Angelo </a></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>IV</td>
                                        <td>B</td>
                                        <td>88%</td>
                                        <td>Practical</td>
                                        <td>
                                            <span class="badge bg-danger">Fail</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Exam Result -->

            <!-- Notice Board -->
            <div class="col-xxl-4 col-xl-5 d-flex">
                <div class="card flex-fill">
                    <div class="card-header  d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Notice Board</h4>
                        <a href="{{url('notice-board')}}" class="fw-medium">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="notice-widget">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center overflow-hidden me-2">
                                    <span
                                        class="bg-primary-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                                        <i class="ti ti-books fs-16"></i>
                                    </span>
                                    <div class="overflow-hidden">
                                        <h6 class="text-truncate mb-1">New Syllabus Instructions</h6>
                                        <p><i class="ti ti-calendar me-2"></i>Added on : 11 Mar 2024</p>
                                    </div>
                                </div>
                                <a href="{{url('notice-board')}}"><i class="ti ti-chevron-right fs-16"></i></a>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center overflow-hidden me-2">
                                    <span
                                        class="bg-success-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                                        <i class="ti ti-note fs-16"></i>
                                    </span>
                                    <div class="overflow-hidden">
                                        <h6 class="text-truncate mb-1">World Environment Day Program.....!!!
                                        </h6>
                                        <p><i class="ti ti-calendar me-2"></i>Added on : 21 Apr 2024</p>
                                    </div>
                                </div>
                                <a href="{{url('notice-board')}}"><i class="ti ti-chevron-right fs-16"></i></a>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center overflow-hidden me-2">
                                    <span
                                        class="bg-danger-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                                        <i class="ti ti-bell-check fs-16"></i>
                                    </span>
                                    <div class="overflow-hidden">
                                        <h6 class="text-truncate mb-1">Exam Preparation Notification!</h6>
                                        <p><i class="ti ti-calendar me-2"></i>Added on : 13 Mar 2024</p>
                                    </div>
                                </div>
                                <a href="{{url('notice-board')}}"><i class="ti ti-chevron-right fs-16"></i></a>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center overflow-hidden me-2">
                                    <span
                                        class="bg-skyblue-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                                        <i class="ti ti-notes fs-16"></i>
                                    </span>
                                    <div class="overflow-hidden">
                                        <h6 class="text-truncate mb-1">Online Classes Preparation</h6>
                                        <p><i class="ti ti-calendar me-2"></i>Added on : 24 May 2024</p>
                                    </div>
                                </div>
                                <a href="{{url('notice-board')}}"><i class="ti ti-chevron-right fs-16"></i></a>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center overflow-hidden me-2">
                                    <span
                                        class="bg-warning-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                                        <i class="ti ti-package fs-16"></i>
                                    </span>
                                    <div class="overflow-hidden">
                                        <h6 class="text-truncate mb-1">Exam Time Table Release</h6>
                                        <p><i class="ti ti-calendar me-2"></i>Added on : 24 May 2024</p>
                                    </div>
                                </div>
                                <a href="{{url('notice-board')}}"><i class="ti ti-chevron-right fs-16"></i></a>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-0">
                                <div class="d-flex align-items-center overflow-hidden me-2">
                                    <span
                                        class="bg-danger-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                                        <i class="ti ti-bell-check fs-16"></i>
                                    </span>
                                    <div class="overflow-hidden">
                                        <h6 class="text-truncate mb-1">English Exam Preparation</h6>
                                        <p><i class="ti ti-calendar me-2"></i>Added on : 23 Mar 2024</p>
                                    </div>
                                </div>
                                <a href="{{url('notice-board')}}"><i class="ti ti-chevron-right fs-16"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- /Notice Board -->

        </div>
    </div>
</div>
<!-- /Page Wrapper -->



@component('components.modal-popup')        
@endcomponent
@endsection