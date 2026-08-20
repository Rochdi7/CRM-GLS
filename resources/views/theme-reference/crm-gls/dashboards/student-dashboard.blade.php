<?php $page ='student-dashboard'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">

        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Student Dashboard</h3>
                 <nav>
                       <ol class="breadcrumb mb-0">
                          <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                          </li>
                          <li class="breadcrumb-item active" aria-current="page">Student Dashboard</li>
                    </ol>
                  </nav>
            </div>
        </div>
        <!-- /Page Header -->

        <div class="row">
            <div class="col-xxl-8 d-flex">
                <div class="row flex-fill">

                    <!-- Profile -->    
                    <div class="col-xl-6 d-flex">
                        <div class="flex-fill">
                            <div class="card bg-dark position-relative">
                                <div class="card-body">
                                    <div class="d-flex align-items-center row-gap-3 mb-3">
                                        <div class="avatar avatar-xxl rounded flex-shrink-0 me-3">
                                            <img src="{{URL::asset('build/img/students/student-13.jpg')}}" alt="Img">
                                        </div>
                                        <div class="d-block">
                                            <span class="badge bg-transparent-primary text-primary mb-1">#ST1234546</span>
                                            <h3 class="text-truncate text-white mb-1">Angelo Riana</h3>
                                            <div class="d-flex align-items-center flex-wrap row-gap-2 text-gray-2">
                                                <span class="border-end me-2 pe-2">Class : III, C</span>
                                                <span>Roll No : 36545</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between profile-footer flex-wrap row-gap-3 pt-4">
                                        <div class="d-flex align-items-center">
                                            <h6 class="text-white">1st Quarterly</h6>
                                            <span class="badge bg-success d-inline-flex align-items-center ms-2"><i class="ti ti-circle-filled fs-5 me-1"></i>Pass</span>
                                        </div>
                                        <a href="{{url('edit-student')}}" class="btn btn-primary">Edit Profile</a>
                                    </div>
                                    <div class="student-card-bg">
                                        <img src="{{URL::asset('build/img/bg/circle-shape.png')}}" alt="Bg">
                                        <img src="{{URL::asset('build/img/bg/shape-02.png')}}" alt="Bg">
                                        <img src="{{URL::asset('build/img/bg/shape-04.png')}}" alt="Bg">
                                        <img src="{{URL::asset('build/img/bg/blue-polygon.png')}}" alt="Bg">
                                    </div>
                                </div>
                            </div>
                            <div class="card flex-fill">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h4 class="card-title">Today’s Class</h4>
                                    <div class="d-inline-flex align-items-center class-datepick">
                                        <span class="icon"><i class="ti ti-chevron-left me-2"></i></span>
                                        <input type="text" class="form-control datetimepicker border-0" placeholder="16 May 2024">
                                        <span class="icon"><i class="ti ti-chevron-right"></i></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="card mb-3">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap p-3 pb-1">
                                            <div class="d-flex align-items-center flex-wrap mb-2">
                                                <span class="avatar avatar-lg flex-shrink-0 rounded me-2">
                                                    <img src="{{URL::asset('build/img/parents/parent-07.jpg')}}" alt="Profile">
                                                </span>
                                                <div>
                                                    <h6 class="mb-1 text-decoration-line-through">English</h6>
                                                    <span><i class="ti ti-clock me-2"></i>09:00 - 09:45 AM</span>
                                                </div>
                                            </div>
                                            <span class="badge badge-soft-success shadow-none mb-2"><i class="ti ti-circle-filled fs-8 me-1"></i>Completed</span>
                                        </div>	
                                    </div>	
                                    <div class="card mb-3">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap p-3 pb-1">
                                            <div class="d-flex align-items-center flex-wrap mb-2">
                                                <span class="avatar avatar-lg flex-shrink-0 rounded me-2">
                                                    <img src="{{URL::asset('build/img/parents/parent-02.jpg')}}" alt="Profile">
                                                </span>
                                                <div>
                                                    <h6 class="mb-1 text-decoration-line-through">Chemistry</h6>
                                                    <span><i class="ti ti-clock me-2"></i>10:45 - 11:30 AM</span>
                                                </div>
                                            </div>
                                            <span class="badge badge-soft-success shadow-none mb-2"><i class="ti ti-circle-filled fs-8 me-1"></i>Completed</span>
                                        </div>	
                                    </div>
                                    <div class="card mb-0">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap p-3 pb-1">
                                            <div class="d-flex align-items-center flex-wrap mb-2">
                                                <span class="avatar avatar-lg flex-shrink-0 rounded me-2">
                                                    <img src="{{URL::asset('build/img/profiles/avatar-17.jpg')}}" alt="Profile">
                                                </span>
                                                <div>
                                                    <h6 class="mb-1">Physics</h6>
                                                    <span><i class="ti ti-clock me-2"></i>11:30 - 12:15 AM</span>
                                                </div>
                                            </div>
                                            <span class="badge badge-soft-warning shadow-none mb-2"><i class="ti ti-circle-filled fs-8 me-1"></i>Inprogress</span>
                                        </div>	
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /Profile -->    

                    <!-- Attendance -->    
                    <div class="col-xl-6 d-flex">
                        <div class="card flex-fill">
                            <div class="card-header d-flex align-items-center justify-content-between">
                                <h4 class="card-title">Attendance</h4>
                                <div class="card-dropdown">
                                    <a href="javascript:void(0);" class="dropdown-toggle p-2" data-bs-toggle="dropdown">
                                        <span><i class="ti ti-calendar-due"></i></span>
                                        This Week
                                    </a>
                                    <div class="dropdown-menu  dropdown-menu-end">
                                        <ul>
                                            <li>
                                                <a href="javascript:void(0);">This Week</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);">Last Week</a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);">Last Month</a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="attendance-chart">											
                                    <p class="mb-3"><i class="ti ti-calendar-heart text-primary me-2"></i>No of total working days <span class="fw-medium text-dark"> 28 Days</span></p>
                                    <div class="border rounded p-3">
                                        <div class="row">
                                            <div class="col text-center border-end">
                                                <p class="mb-1">Present</p>
                                                <h5>25</h5>
                                            </div>
                                            <div class="col text-center border-end">
                                                <p class="mb-1">Absent</p>
                                                <h5>2</h5>
                                            </div>
                                            <div class="col text-center">
                                                <p class="mb-1">Halfday</p>
                                                <h5>0</h5>
                                            </div>
                                        </div>
                                    </div>   
                                    <div class="text-center">
                                        <div id="attendance_chart"></div>
                                    </div>
                                    <div class="bg-light-300 rounded border p-3 mb-0">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-1">
                                            <h6 class="mb-2">Last 7 Days </h6>
                                            <p class="fs-12 mb-2">14 May 2024 - 21 May 2024</p>
                                        </div>
                                        <div class="d-flex align-items-center rounded gap-1 flex-wrap">
                                            <a href="javascript:void(0);" class="badge badge-lg bg-success text-white">M</a>
                                            <a href="javascript:void(0);" class="badge badge-lg bg-success text-white">T</a>
                                            <a href="javascript:void(0);" class="badge badge-lg bg-success text-white">W</a>
                                            <a href="javascript:void(0);" class="badge badge-lg bg-success text-white">T</a>
                                            <a href="javascript:void(0);" class="badge badge-lg bg-danger text-white">F</a>
                                            <a href="javascript:void(0);" class="badge badge-lg bg-white border text-default">S</a>
                                            <a href="javascript:void(0);" class="badge badge-lg  bg-white border text-gray-1">S</a>
                                        </div>
                                    </div>
                                </div>
                            
                            </div>
                        </div>
                    </div>
                    <!-- /Attendance -->    

                    <!-- Fees -->    
                    <div class="col-xl-12 d-flex">
                        <div class="row flex-fill">
                            <div class="col-sm-6 col-xl-3 d-flex">
                                <a href="{{url('student-fees')}}" class="card border-0 border-bottom border-primary border-2 flex-fill animate-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-md rounded bg-primary me-2"><i class="ti ti-report-money fs-16"></i></span>
                                            <h6>Pay Fees</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-sm-6 col-xl-3 d-flex">
                                <a href="{{url('student-result')}}" class="card border-0 border-bottom border-success flex-fill animate-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-md rounded bg-success me-2"><i class="ti ti-hexagonal-prism-plus fs-16"></i></span>
                                            <h6>Exam Result</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-sm-6 col-xl-3 d-flex">
                                <a href="{{url('student-time-table')}}" class="card border-0 border-bottom border-warning flex-fill animate-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-md rounded bg-warning me-2"><i class="ti ti-calendar fs-16"></i></span>
                                            <h6>Calendar</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-sm-6 col-xl-3 d-flex">
                                <a href="{{url('student-leaves')}}" class="card border-0 border-bottom border-dark border-2 flex-fill animate-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-md rounded bg-dark me-2"><i class="ti ti-calendar-share fs-16"></i></span>
                                            <h6>Attendance</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>												
                        </div>	
                    </div>
                    <!-- /Fees -->    

                </div>
            </div>

            <!-- Schedules -->    
            <div class="col-xxl-4 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Schedules</h4>
                        <a href="#" class="link-primary fw-medium me-2" data-bs-toggle="modal" data-bs-target="#add_exam_schedule"><i class="ti ti-square-plus me-1"></i>Add New</a>
                    </div>
                    <div class="card-body pb-0">
                        <div class="datepic mb-2"></div>
                        <h5 class="mb-3">Exams</h5>
                        <div class="p-3 pb-0 mb-3 border rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="mb-3">1st Quarterly</h5>
                                <span class="badge badge-soft-danger d-inline-flex align-items-center mb-3"><i class="ti ti-clock me-1"></i>19 Days More</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="mb-3">
                                    <h6 class="mb-1">Mathematics</h6>
                                    <p><i class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
                                </div>
                                <div class="mb-3 text-end">
                                    <p class="mb-1"><i class="ti ti-calendar-bolt me-1"></i>06 May 2024</p>
                                    <p class="text-primary">Room No : 15</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-3 pb-0 mb-3 border rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="mb-3">2nd Quarterly</h5>
                                <span class="badge badge-soft-danger d-inline-flex align-items-center mb-3"><i class="ti ti-clock me-1"></i>20 Days More</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="mb-3">
                                    <h6 class="mb-1">Physics</h6>
                                    <p><i class="ti ti-clock me-1"></i>01:30 - 02:15 PM</p>
                                </div>
                                <div class="mb-3 text-end">
                                    <p class="mb-1"><i class="ti ti-calendar-bolt me-1"></i>07 May 2024</p>
                                    <p class="text-primary">Room No : 15</p>
                                </div>
                            </div>
                        </div>							
                    </div>
                </div>
            </div>
            <!-- /Schedules -->    

        </div>
        <div class="row">

            <!-- Performance -->    
            <div class="col-xxl-7 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Performance</h4>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                data-bs-toggle="dropdown"><i class="ti ti-calendar me-2"></i>2024 - 2025
                            </a>
                            <ul class="dropdown-menu mt-2 p-3">
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        2024 - 2025
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        2023 - 2024
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        2022 - 2023
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body pb-0">
                        <div id="performance_chart"></div>
                    </div>
                </div>
            </div>
            <!-- /Performance -->    

            <!-- Home Works -->    
            <div class="col-xxl-5 d-flex">
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
                            <li class="list-group-item py-3 px-0 pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="d-flex align-items-center overflow-hidden mb-3">
                                        <a href="javascript:void(0);" class="avatar avatar-xl flex-shrink-0 me-2">
                                            <img src="{{URL::asset('build/img/home-work/home-work-01.jpg')}}" alt="img">
                                        </a>
                                        <div class="overflow-hidden">
                                            <p class="d-flex align-items-center text-info mb-1"><i class="ti ti-tag me-2"></i>Physics</p>
                                            <h6 class="text-truncate mb-1"><a href="{{url('class-home-work')}}">Write about Theory of Pendulum</a></h6>
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div class="d-flex align-items-center border-end me-1 pe-1">
                                                    <a href="{{url('teacher-details')}}" class="avatar avatar-xs flex-shrink-0 me-2">
                                                        <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="rounded-circle" alt="teacher">
                                                    </a>
                                                    <p class="text-dark">Aaron</p>
                                                </div>
                                                <p>Due by : 16 Jun 2024</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="circle-progress mb-3"  data-value='80'>
                                        <span class="progress-left">
                                            <span class="progress-bar border-success"></span>
                                        </span>
                                        <span class="progress-right">
                                            <span class="progress-bar border-success"></span>
                                        </span>
                                        <div class="progress-value">90%</div>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0 pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="d-flex align-items-center overflow-hidden mb-3">
                                        <a href="javascript:void(0);" class="avatar avatar-xl flex-shrink-0 me-2">
                                            <img src="{{URL::asset('build/img/home-work/home-work-02.jpg')}}" alt="img">
                                        </a>
                                        <div class="overflow-hidden">
                                            <p class="d-flex align-items-center text-success mb-1"><i class="ti ti-tag me-2"></i>Chemistry</p>
                                            <h6 class="text-truncate mb-1"><a href="{{url('class-home-work')}}">Chemistry - Change of Elements</a></h6>
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div class="d-flex align-items-center border-end me-1 pe-1">
                                                    <a href="{{url('teacher-details')}}" class="avatar avatar-xs flex-shrink-0 me-2">
                                                        <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="rounded-circle" alt="teacher">
                                                    </a>
                                                    <p class="text-dark">Hellana</p>
                                                </div>
                                                <p>Due by : 16 Jun 2024</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="circle-progress mb-3"  data-value='65'>
                                        <span class="progress-left">
                                            <span class="progress-bar border-info"></span>
                                        </span>
                                        <span class="progress-right">
                                            <span class="progress-bar border-info"></span>
                                        </span>
                                        <div class="progress-value">65%</div>	
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0 pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="d-flex align-items-center overflow-hidden mb-3">
                                        <a href="javascript:void(0);" class="avatar avatar-xl flex-shrink-0 me-2">
                                            <img src="{{URL::asset('build/img/home-work/home-work-03.jpg')}}" alt="img">
                                        </a>
                                        <div class="overflow-hidden">
                                            <p class="d-flex align-items-center text-danger mb-1"><i class="ti ti-tag me-2"></i>Maths</p>
                                            <h6 class="text-truncate mb-1"><a href="{{url('class-home-work')}}">Maths - Problems to Solve Page 21</a></h6>
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div class="d-flex align-items-center border-end me-1 pe-1">
                                                    <a href="{{url('teacher-details')}}" class="avatar avatar-xs flex-shrink-0 me-2">
                                                        <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="rounded-circle" alt="teacher">
                                                    </a>
                                                    <p class="text-dark">Morgan</p>
                                                </div>
                                                <p>Due by : 21 Jun 2024</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="circle-progress mb-3"  data-value='30'>
                                        <span class="progress-left">
                                            <span class="progress-bar border-warning"></span>
                                        </span>
                                        <span class="progress-right">
                                            <span class="progress-bar border-warning"></span>
                                        </span>
                                        <div class="progress-value">30%</div>
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0 pb-0">
                                <div class="d-flex align-items-center justify-content-between flex-wrap">
                                    <div class="d-flex align-items-center overflow-hidden mb-3">
                                        <a href="javascript:void(0);" class="avatar avatar-xl flex-shrink-0 me-2">
                                            <img src="{{URL::asset('build/img/home-work/home-work-04.jpg')}}" alt="img">
                                        </a>
                                        <div class="overflow-hidden">
                                            <p class="d-flex align-items-center text-skyblue mb-1"><i class="ti ti-tag me-2"></i>Engish</p>
                                            <h6 class="text-truncate mb-1"><a href="{{url('class-home-work')}}">English - Vocabulary Introduction</a></h6>
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div class="d-flex align-items-center border-end me-1 pe-1">
                                                    <a href="{{url('teacher-details')}}" class="avatar avatar-xs flex-shrink-0 me-2">
                                                        <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="rounded-circle" alt="teacher">
                                                    </a>
                                                    <p class="text-dark">Daniel Josua</p>
                                                </div>
                                                <p>Due by : 21 Jun 2024</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="circle-progress mb-3"  data-value='10'>
                                        <span class="progress-left">
                                            <span class="progress-bar border-danger"></span>
                                        </span>
                                        <span class="progress-right">
                                            <span class="progress-bar border-danger"></span>
                                        </span>
                                        <div class="progress-value">10%</div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /Home Works -->    

        </div>

        <div class="row">

            <!-- Class Faculties -->    
            <div class="col-xl-12">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Class Faculties</h4>
                        <div class="owl-nav slide-nav text-end nav-control"></div>
                    </div>
                    <div class="card-body">
                        <div class="teachers-profile-slider owl-carousel">
                            <div class="card bg-light-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <a href="{{url('teacher-details')}}" class="avatar avatar-lg rounded me-2">
                                            <img src="{{URL::asset('build/img/teachers/teacher-06.jpg')}}" alt="Teacher">
                                        </a>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1 text-truncate"><a href="{{url('teacher-details')}}">Aaron</a></h6>
                                            <p>Chemistry</p>
                                        </div>
                                    </div>
                                    <div class="row gx-2">
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-mail me-2"></i>Email</a>
                                        </div>
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-message-chatbot me-2"></i>Chat</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card bg-light-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <a href="{{url('teacher-details')}}" class="avatar avatar-lg rounded me-2">
                                            <img src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}" alt="Teacher">
                                        </a>
                                        <div>
                                            <h6 class="mb-1 text-truncate"><a href="{{url('teacher-details')}}">Hellana</a></h6>
                                            <p>English</p>
                                        </div>
                                    </div>
                                    <div class="row gx-2">
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-mail me-2"></i>Email</a>
                                        </div>
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-message-chatbot me-2"></i>Chat</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card bg-light-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <a href="{{url('teacher-details')}}" class="avatar avatar-lg rounded me-2">
                                            <img src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}" alt="Teacher">
                                        </a>
                                        <div>
                                            <h6 class="mb-1 text-truncate"><a href="{{url('teacher-details')}}">Morgan</a></h6>
                                            <p>Physics</p>
                                        </div>
                                    </div>
                                    <div class="row gx-2">
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-mail me-2"></i>Email</a>
                                        </div>
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-message-chatbot me-2"></i>Chat</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card bg-light-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <a href="{{url('teacher-details')}}" class="avatar avatar-lg rounded me-2">
                                            <img src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}" alt="Teacher">
                                        </a>
                                        <div>
                                            <h6 class="mb-1 text-truncate"><a href="{{url('teacher-details')}}">Daniel Josua</a></h6>
                                            <p>Spanish</p>
                                        </div>
                                    </div>
                                    <div class="row gx-2">
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-mail me-2"></i>Email</a>
                                        </div>
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-message-chatbot me-2"></i>Chat</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card bg-light-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <a href="{{url('teacher-details')}}" class="avatar avatar-lg rounded me-2">
                                            <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" alt="Teacher">
                                        </a>
                                        <div>
                                            <h6 class="mb-1 text-truncate"><a href="{{url('teacher-details')}}">Teresa</a></h6>
                                            <p>Maths</p>
                                        </div>
                                    </div>
                                    <div class="row gx-2">
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-mail me-2"></i>Email</a>
                                        </div>
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-message-chatbot me-2"></i>Chat</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card bg-light-100 mb-0">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <a href="{{url('teacher-details')}}" class="avatar avatar-lg rounded me-2">
                                            <img src="{{URL::asset('build/img/teachers/teacher-09.jpg')}}" alt="Teacher">
                                        </a>
                                        <div>
                                            <h6 class="mb-1 text-truncate"><a href="{{url('teacher-details')}}">Jacquelin</a></h6>
                                            <p>Biology</p>
                                        </div>
                                    </div>
                                    <div class="row gx-2">
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-mail me-2"></i>Email</a>
                                        </div>
                                        <div class="col-6">
                                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white d-flex align-items-center justify-content-center fw-semibold fs-12"><i class="ti ti-message-chatbot me-2"></i>Chat</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>																																					
                    </div>
                </div>
            </div>
            <!-- /Class Faculties -->    

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
                                <div class="avatar avatar-lg bg-danger-transparent flex-shrink-0 me-2"><i class="ti ti-brand-socket-io"></i></div>
                                <div>
                                    <h6 class="mb-1">Emergency Leave</h6>
                                    <p>Date : 15 Jun 2024</p>
                                </div>
                            </div>
                            <span class="badge bg-skyblue d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                        </div>
                        <div class="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-3">
                            <div class="d-flex align-items-center mb-2 mb-sm-0">
                                <div class="avatar avatar-lg bg-info-transparent flex-shrink-0 me-2"><i class="ti ti-medical-cross"></i></div>
                                <div>
                                    <h6 class="mb-1">Medical Leave</h6>
                                    <p>Date : 15 Jun 2024</p>
                                </div>
                            </div>
                            <span class="badge bg-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                        </div>
                        <div class="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-3">
                            <div class="d-flex align-items-center mb-2 mb-sm-0">
                                <div class="avatar avatar-lg bg-info-transparent flex-shrink-0 me-2"><i class="ti ti-medical-cross"></i></div>
                                <div>
                                    <h6 class="mb-1">Medical Leave</h6>
                                    <p>Date : 16 Jun 2024</p>
                                </div>
                            </div>
                            <span class="badge bg-danger d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Declined</span>
                        </div>
                        <div class="bg-light-300 d-sm-flex align-items-center justify-content-between p-3 mb-0">
                            <div class="d-flex align-items-center mb-2 mb-sm-0">
                                <div class="avatar avatar-lg bg-danger-transparent flex-shrink-0 me-2"><i class="ti ti-brand-socket-io"></i></div>
                                <div>
                                    <h6 class="mb-1">Fever</h6>
                                    <p>Date : 16 Jun 2024</p>
                                </div>
                            </div>
                            <span class="badge bg-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Leave Status -->  

            <!-- Exam Result -->     
            <div class="col-xxl-4 col-xl-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Exam Result</h4>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                data-bs-toggle="dropdown"><i class="ti ti-calendar me-2"></i>1st Quarter
                            </a>
                            <ul class="dropdown-menu mt-2 p-3">
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        1st Quarter
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                        2nd Quarter
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body pb-0">
                        <div class="d-flex align-items-center flex-wrap">
                            <span class="badge badge-soft-primary badge-md me-1 mb-3">Mat : 100 </span>
                            <span class="badge badge-soft-success badge-md me-1 mb-3">Phy: 92</span>
                            <span class="badge badge-soft-warning badge-md me-1 mb-3">Che : 90</span>
                            <span class="badge badge-soft-danger badge-md mb-3">Eng : 80</span>
                        </div>
                        <div id="exam-result-chart"></div>
                    </div>
                </div>
            </div>
            <!-- /Exam Result -->    

            <!-- Fees Reminder -->    
            <div class="col-xxl-4 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <h4 class="card-titile">Fees Reminder</h4>                                   
                        <a href="{{url('fees-assign')}}" class="link-primary fw-medium">View All</a>
                    </div>
                    <div class="card-body py-1">
                        <div class="d-flex align-items-center justify-content-between py-3">
                            <div class="d-flex align-items-center overflow-hidden me-2">
                                <span class="bg-info-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                                    <i class="ti ti-bus-stop fs-16"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <h6 class="text-truncate mb-1">Transport  Fees</h6>
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
                                <span class="bg-success-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
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
                                <span class="bg-info-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
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
                                <span class="bg-skyblue-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
                                    <i class="ti ti-meat fs-16"></i>
                                </span>
                                <div class="overflow-hidden">
                                    <h6 class="text-truncate mb-1">Mess Fees <span class="d-inline-flex align-items-center badge badge-soft-danger"><i class="ti ti-circle-filled me-1 fs-5"></i>Due</span></h6>
                                    <p class="text-danger">$2500 + $150</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <h6 class="mb-1">Last Date</h6>
                                <p>27 May 2024</p>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between py-3">
                            <div class="d-flex align-items-center overflow-hidden me-2">
                                <span class="bg-danger-transparent avatar avatar-lg me-2 rounded-circle flex-shrink-0">
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
            
            <!-- Notice Board -->
            <div class="col-xxl-4 col-xl-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header  d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Notice Board</h4>
                        <a href="{{url('notice-board')}}" class="fw-medium">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="notice-widget">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center overflow-hidden me-2">
                                    <span class="bg-primary-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
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
                                    <span class="bg-success-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
                                        <i class="ti ti-note fs-16"></i>
                                    </span>
                                    <div class="overflow-hidden">
                                        <h6 class="text-truncate mb-1">World Environment Day Program.....!!!</h6>
                                        <p><i class="ti ti-calendar me-2"></i>Added on : 21 Apr 2024</p>
                                    </div>
                                </div>
                                <a href="{{url('notice-board')}}"><i class="ti ti-chevron-right fs-16"></i></a>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div class="d-flex align-items-center overflow-hidden me-2">
                                    <span class="bg-danger-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
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
                                    <span class="bg-skyblue-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
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
                                    <span class="bg-warning-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
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
                                    <span class="bg-danger-transparent avatar avatar-md me-2 rounded-circle flex-shrink-0">
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

            <!-- Syllabus -->
            <div class="col-xxl-4 col-xl-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header  d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Syllabus</h4>								
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success d-flex align-items-center mb-24" role="alert">
                            <i class="ti ti-info-square-rounded me-2 fs-14"></i>
                            <div class="fs-14">
                                These Result are obtained from the syllabus completion on the respective Class
                            </div>
                        </div>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <p class="text-dark">Maths</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="progress progress-xs flex-grow-1">
                                            <div class="progress-bar bg-primary rounded" role="progressbar" style="width: 20%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>												
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <p class="text-dark">Physics</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="progress progress-xs flex-grow-1">
                                            <div class="progress-bar bg-secondary rounded" role="progressbar" style="width: 30%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>												
                                    </div>
                                </div>										
                            </li>
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <p class="text-dark">Chemistry</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="progress progress-xs flex-grow-1">
                                            <div class="progress-bar bg-info rounded" role="progressbar" style="width: 40%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>												
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <p class="text-dark">Botany</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="progress progress-xs flex-grow-1">
                                            <div class="progress-bar bg-success rounded" role="progressbar" style="width: 50%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>												
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <p class="text-dark">English</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="progress progress-xs flex-grow-1">
                                            <div class="progress-bar bg-warning rounded" role="progressbar" style="width: 70%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>												
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <p class="text-dark">Spanish</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="progress progress-xs flex-grow-1">
                                            <div class="progress-bar bg-danger rounded" role="progressbar" style="width: 80%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>												
                                    </div>
                                </div>
                            </li>
                            <li class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-sm-4">
                                        <p class="text-dark">Japanese</p>
                                    </div>
                                    <div class="col-sm-8">
                                        <div class="progress progress-xs flex-grow-1">
                                            <div class="progress-bar bg-primary rounded" role="progressbar" style="width: 85%;" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>												
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
            <!-- /Syllabus -->

            <!-- Todo -->
            <div class="col-xxl-4 col-xl-12 d-flex">
                <div class="card flex-fill">
                    <div class="card-header  d-flex align-items-center justify-content-between">
                        <h4 class="card-title">Todo</h4>
                        <div class="dropdown">
                            <a href="javascript:void(0);" class="bg-white dropdown-toggle"
                                data-bs-toggle="dropdown"><i class="ti ti-calendar me-2"></i>Today
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
                        <ul class="list-group list-group-flush todo-list">
                            <li class="list-group-item py-3 px-0 pt-0">
                                <div class="d-sm-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center overflow-hidden me-2 todo-strike-content">
                                        <div class="form-check form-check-md me-2">
                                            <input class="form-check-input" type="checkbox" checked>
                                        </div>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1 text-truncate">Send Reminder to Students</h6>
                                            <p>01:00 PM</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-success mt-2 mt-sm-0">Compeleted</span>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0">
                                <div class="d-sm-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center overflow-hidden me-2">
                                        <div class="form-check form-check-md me-2">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1 text-truncate">Create Routine to new staff</h6>
                                            <p>04:50 PM</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-skyblue mt-2 mt-sm-0">Inprogress</span>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0">
                                <div class="d-sm-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center overflow-hidden me-2">
                                        <div class="form-check form-check-md me-2">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1 text-truncate">Extra Class Info to Students</h6>
                                            <p>04:55 PM</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-warning mt-2 mt-sm-0">Yet to Start</span>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0">
                                <div class="d-sm-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center overflow-hidden me-2">
                                        <div class="form-check form-check-md me-2">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1 text-truncate">Fees for Upcoming Academics</h6>
                                            <p>04:55 PM</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-warning mt-2 mt-sm-0">Yet to Start</span>
                                </div>
                            </li>
                            <li class="list-group-item py-3 px-0 pb-0">
                                <div class="d-sm-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center overflow-hidden me-2">
                                        <div class="form-check form-check-md me-2">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                        <div class="overflow-hidden">
                                            <h6 class="mb-1 text-truncate">English - Essay on Visit</h6>
                                            <p>05:55 PM</p>
                                        </div>
                                    </div>
                                    <span class="badge badge-soft-warning mt-2 mt-sm-0">Yet to Start</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- /Todo -->

        </div>
    </div>
</div>
<!-- /Page Wrapper -->

@component('components.modal-popup')        
@endcomponent
@endsection