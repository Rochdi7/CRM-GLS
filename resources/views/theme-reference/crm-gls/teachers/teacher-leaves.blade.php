<?php $page = 'teacher-leaves'; ?>
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
                    <h3 class="page-title mb-1">Teacher Details</h3>
                     <nav>
                           <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item">
                                    <a href="{{url('index')}}">Dashboard</a>
                                  </li>
                                  <li class="breadcrumb-item">
                                    <a href="{{url('teachers')}}">Teachers</a>
                                  </li>
                                  <li class="breadcrumb-item active" aria-current="page">Teacher Details</li>
                            </ol>
                      </nav>
                </div>
                <div class="d-flex my-xl-auto right-content align-items-center  flex-wrap">			
                    <a href="#" class="btn btn-light me-2 mb-2" data-bs-toggle="modal" data-bs-target="#login_detail"><i class="ti ti-lock me-2"></i>Login Details</a>					
                    <a href="{{url('edit-teacher')}}" class="btn btn-primary d-flex align-items-center mb-2"><i class="ti ti-edit-circle me-2"></i>Edit Teacher</a> 
                </div>
            </div>
        </div>
        <!-- /Page Header -->
        </div>

        <div class="row">

            <!-- Student Information -->
            <div class="col-xxl-3 col-lg-4 theiaStickySidebar">
                <div class="card border-white">
                    <div class="card-header">
                        <div class="d-flex align-items-center flex-wrap row-gap-3">                                                
                            <div class="d-flex align-items-center justify-content-center avatar avatar-xxl border border-dashed me-2 flex-shrink-0 text-dark frames">
                                <img src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}" class="img-fluid" alt="img">
                            </div>                                              
                            <div>
                                <h5 class="mb-1">Teresa</h5>
                                <p class="text-primary mb-1">T849126</p>
                                <p>Joined : 25 May 24</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <h5 class="mb-3">Basic Information</h5>
                        <dl class="row mb-0"> 
                            <dt class="col-6 fw-normal mb-3">Class & Section</dt> 
                            <dd class="col-6 text-dark mb-3">III, A</dd> 
                            <dt class="col-6 fw-normal mb-3">Subject</dt> 
                            <dd class="col-6 text-dark mb-3">Physics</dd> 
                            <dt class="col-6 fw-normal mb-3">Gender</dt> 
                            <dd class="col-6 text-dark mb-3">Female</dd> 
                            <dt class="col-6 fw-normal mb-3">Blood Group</dt> 
                            <dd class="col-6 text-dark mb-3">O +ve</dd> 
                            <dt class="col-6 fw-normal mb-3">House</dt> 
                            <dd class="col-6 text-dark mb-3">Red</dd> 
                            <dt class="col-6 fw-normal mb-3">Language Known</dt> 
                            <dd class="col-6 text-dark mb-3">English</dd> 
                            <dt class="col-6 fw-normal mb-0">Language</dt> 
                            <dd class="col-6 text-dark mb-0"><span class="badge badge-light text-dark me-2">English</span><span class="badge badge-light text-dark">Spanish</span></dd> 
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
                <div class="card border-white">
                    <div class="card-body pb-1">
                        <h5 class="mb-3">PAN Number / ID Number</h5>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center mb-3">
                                <span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-id"></i></span>
                                <div>										
                                    <p class="text-dark">343445954908</p>
                                </div>
                            </div>	
                            <a class="btn btn-primary btn-icon btn-sm mb-3">
                                <i class="ti ti-copy"></i>
                            </a>				
                        </div>		
                    </div>
                </div>
                <div class="card border-white">
                    <div class="card-body pb-1">
                        <ul class="nav nav-tabs nav-tabs-bottom mb-3">
                            <li class="nav-item"><a class="nav-link active" href="#hostel" data-bs-toggle="tab">Hostel</a></li>
                            <li class="nav-item"><a class="nav-link" href="#transport" data-bs-toggle="tab">Transportation</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="hostel">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-building-fortress fs-16"></i></span>
                                    <div>
                                        <h6 class="mb-1">HI-Hostel, Floor</h6>
                                        <p class="text-primary">Room No : 25</p>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="transport">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="avatar avatar-md bg-light-300 rounded me-2 flex-shrink-0 text-default"><i class="ti ti-bus fs-16"></i></span>
                                    <div>
                                        <span class="fs-12 mb-1">Route</span>
                                        <p class="text-dark">Newyork</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <span class="fs-12 mb-1">Bus Number</span>
                                            <p class="text-dark">AM 54548</p>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="mb-3">
                                            <span class="fs-12 mb-1">Pickup Point</span>
                                            <p class="text-dark">Cincinatti</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Student Information -->

            <div class="col-xxl-9 col-xl-8">
                <div class="row">
                    <div class="col-md-12">

                        <!-- List -->
                        <ul class="nav nav-tabs nav-tabs-bottom mb-4">
                            <li>
                                <a href="{{url('teacher-details')}}" class="nav-link "><i class="ti ti-school me-2"></i>Teacher Details</a>
                            </li>
                            <li>
                                <a href="{{url('routine-teachers')}}" class="nav-link"><i class="ti ti-table-options me-2"></i>Routine</a>
                            </li>
                            <li>
                                <a href="{{url('teacher-leaves')}}" class="nav-link active"><i class="ti ti-calendar-due me-2"></i>Leave & Attendance</a>
                            </li>
                            <li>
                                <a href="{{url('teacher-salary')}}" class="nav-link"><i class="ti ti-report-money me-2"></i>Salary</a>
                            </li>
                            <li>
                                <a href="{{url('teacher-library')}}" class="nav-link"><i class="ti ti-bookmark-edit me-2"></i>Library</a>
                            </li>
                            
                        </ul>
                        <!-- /List -->					
                        
                        <!-- Leave Nav-->
                        <div class="card">
                            <div class="card-body pb-1">
                                <ul class="nav nav-tabs nav-tabs-solid nav-tabs-rounded-fill">
                                    <li class="me-3 mb-3">
                                        <a href="#" class="nav-link active rounded fs-12 fw-semibold" data-bs-toggle="tab" data-bs-target="#leave">Leaves</a>
                                    </li>
                                    <li class="mb-3">
                                        <a href="#" class="nav-link rounded fs-12 fw-semibold" data-bs-toggle="tab" data-bs-target="#attendance">Attendance</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <!-- /Leave Nav-->

                        <div class="tab-content">

                            <!-- Leave -->
                            <div class="tab-pane fade show active" id="leave">
                                <div class="row gx-3">
                                    <div class="col-lg-6 col-xxl-3 d-flex">
                                        <div class="card flex-fill">
                                            <div class="card-body">
                                                <h5 class="mb-2">Medical Leave (10)</h5>
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <p class="border-end pe-2 me-2 mb-0">Used : 5</p>
                                                    <p class="mb-0">Available : 5</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-xxl-3 d-flex">
                                        <div class="card flex-fill">
                                            <div class="card-body">
                                                <h5 class="mb-2">Casual Leave (12)</h5>
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <p class="border-end pe-2 me-2 mb-0">Used : 1</p>
                                                    <p class="mb-0">Available : 11</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-xxl-3 d-flex">
                                        <div class="card flex-fill">
                                            <div class="card-body">
                                                <h5 class="mb-2">Maternity Leave (10)</h5>
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <p class="border-end pe-2 me-2 mb-0">Used : 0</p>
                                                    <p class="mb-0">Available : 10</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-xxl-3 d-flex">
                                        <div class="card flex-fill">
                                            <div class="card-body">
                                                <h5 class="mb-2">Paternity Leave (0)</h5>
                                                <div class="d-flex align-items-center flex-wrap">
                                                    <p class="border-end pe-2 me-2 mb-0">Used : 0</p>
                                                    <p class="mb-0">Available : 0</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card">		
                                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                                        <h4 class="mb-3">Leaves</h4>
                                        <a href="#" data-bs-target="#apply_leave" data-bs-toggle="modal" class="btn btn-primary d-inline-flex align-items-center mb-3"><i class="ti ti-calendar-event me-2"></i>Apply Leave</a>
                                    </div>
                                    <!-- Leaves List -->
                                    <div class="card-body p-0 py-3">
                                        <div class="custom-datatable-filter table-responsive">
                                            <table class="table datatable">
                                                <thead class="thead-light">
                                                    <tr>
                                                        <th class="no-sort">Leave Type</th>
                                                        <th class="no-sort">Leave Date</th>
                                                        <th class="no-sort">No of Days</th>
                                                        <th class="no-sort">Applied On</th>
                                                        <th class="no-sort">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td class="text-dark">Medical Leave</td>
                                                        <td>05 May 2024 - 09 may 2024</td>
                                                        <td>5</td>
                                                        <td>05 May 2024 </td>
                                                        <td>
                                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-dark">Casual Leave</td>
                                                        <td>07 May 2024 - 07 may 2024</td>
                                                        <td>1</td>
                                                        <td>07 May 2024 </td>
                                                        <td>
                                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-dark">Special Leave</td>
                                                        <td>09 May 2024 - 09 may 2024</td>
                                                        <td>1</td>
                                                        <td>09 May 2024 </td>
                                                        <td>
                                                            <span
                                                                class="badge badge-soft-pending d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-dark">Casual Leave</td>
                                                        <td>08 May 2024 - 08 may 2024</td>
                                                        <td>1</td>
                                                        <td>04 May 2024 </td>
                                                        <td>
                                                            <span class="badge badge-soft-success d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Approved</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-dark">Medical Leave</td>
                                                        <td>08 May 2024 - 11 may 2024</td>
                                                        <td>4</td>
                                                        <td>08 May 2024 </td>
                                                        <td>
                                                            <span class="badge badge-soft-pending d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-dark">Casual Leave</td>
                                                        <td>20 May 2024 - 20 may 2024</td>
                                                        <td>1</td>
                                                        <td>19 May 2024 </td>
                                                        <td>
                                                            <span class="badge badge-soft-pending d-inline-flex align-items-center"><i class="ti ti-circle-filled fs-5 me-1"></i>Pending</span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- /Leaves List -->
                                </div>

                            </div>
                            <!-- /Leave -->

                            <!-- Attendance -->
                            <div class="tab-pane fade" id="attendance">
                                <div class="card">
                                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-1">
                                        <h4 class="mb-3">Attendance</h4>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <div class="d-flex align-items-center flex-wrap me-3">
                                                <p class="text-dark mb-3 me-2">Last Updated on : 25 May 2024 </p>
                                                <a href="#" class="btn btn-primary btn-icon btn-sm rounded-circle d-inline-flex align-items-center justify-content-center p-0 mb-3"><i class="ti ti-refresh-dot"></i></a>
                                            </div>
                                            <div class="dropdown mb-3">
                                                <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="ti ti-calendar-due me-2"></i>Year : 2024 / 2025</a>
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
                                                <div class="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                                    <span class="avatar avatar-lg bg-primary-transparent rounded me-2 flex-shrink-0 text-primary"><i class="ti ti-user-check fs-24"></i></span>
                                                    <div class="ms-2">
                                                        <p class="mb-1">Total Present</p>
                                                        <h5>265</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- /Total Present -->

                                            <!-- Total Absent -->
                                            <div class="col-md-6 col-xxl-3 d-flex">
                                                <div class="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                                    <span class="avatar avatar-lg bg-danger-transparent rounded me-2 flex-shrink-0 text-danger"><i class="ti ti-user-x fs-24"></i></span>
                                                    <div class="ms-2">
                                                        <p class="mb-1">Total Absent</p>
                                                        <h5>05</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- /Total Absent -->

                                            <!-- Half Day -->
                                            <div class="col-md-6 col-xxl-3 d-flex">
                                                <div class="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                                    <span class="avatar avatar-lg bg-info-transparent rounded me-2 flex-shrink-0 text-info"><i class="ti ti-calendar-event fs-24"></i></span>
                                                    <div class="ms-2">
                                                        <p class="mb-1">Half Day</p>
                                                        <h5>01</h5>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- /Half Day -->

                                            <!-- Late to School-->
                                            <div class="col-md-6 col-xxl-3 d-flex">
                                                <div class="d-flex align-items-center rounded border p-3 mb-3 flex-fill">
                                                    <span class="avatar avatar-lg bg-warning-transparent rounded me-2 flex-shrink-0 text-warning"><i class="ti ti-clock-x fs-24"></i></span>
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
                                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-1">
                                        <h4 class="mb-3">Leave & Attendance</h4>
                                        <div class="d-flex align-items-center flex-wrap">
                                            <div class="dropdown mb-3 me-3">
                                                <a href="javascript:void(0);" class="btn btn-outline-light border-white bg-white dropdown-toggle shadow-md"  data-bs-toggle="dropdown"><i class="ti ti-calendar-due me-2"></i>This Year</a>
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
                                                <a href="javascript:void(0);" class="dropdown-toggle btn btn-light fw-medium d-inline-flex align-items-center"  data-bs-toggle="dropdown">
                                                    <i class="ti ti-file-export me-2"></i>Export
                                                </a>
                                                <ul class="dropdown-menu  dropdown-menu-end p-3">
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-pdf me-2"></i>Export as PDF</a>
                                                    </li>
                                                    <li>
                                                        <a href="javascript:void(0);" class="dropdown-item rounded-1"><i class="ti ti-file-type-xls me-2"></i>Export as Excel </a>
                                                    </li>
                                                </ul>	
                                            </div>   
                                        </div>
                                    </div>
                                    <div class="card-body p-0 py-3">
                                        
                                        <div class="px-3">
                                            <div class="d-flex align-items-center flex-wrap">
                                                <div class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                                    <span class="avatar avatar-sm bg-success rounded me-2 flex-shrink-0 "><i class="ti ti-checks"></i></span>
                                                    <p class="text-dark">Present</p>
                                                </div>
                                                <div class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                                    <span class="avatar avatar-sm bg-danger rounded me-2 flex-shrink-0 "><i class="ti ti-x"></i></span>
                                                    <p class="text-dark">Absent</p>
                                                </div>
                                                <div class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                                    <span class="avatar avatar-sm bg-pending rounded me-2 flex-shrink-0 "><i class="ti ti-clock-x"></i></span>
                                                    <p class="text-dark">Late</p>
                                                </div>
                                                <div class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                                    <span class="avatar avatar-sm bg-dark rounded me-2 flex-shrink-0 "><i class="ti ti-calendar-event"></i></span>
                                                    <p class="text-dark">Halfday</p>
                                                </div>
                                                <div class="d-flex align-items-center bg-white border rounded p-2 me-3 mb-3">
                                                    <span class="avatar avatar-sm bg-info rounded me-2 flex-shrink-0 "><i class="ti ti-calendar-event"></i></span>
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

                            </div>
                            <!-- /Attendance -->

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- /Page Wrapper -->

    @component('components.modal-popup')
    @endcomponent
@endsection
