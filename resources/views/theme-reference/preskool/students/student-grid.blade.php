<?php $page = 'student-grid'; ?>
@extends('layout.mainlayout')
@section('content')
  <!-- Page Wrapper -->
  <div class="page-wrapper">
    <div class="content content-two">
        <!-- Page Header -->
        <div class="d-md-flex d-block align-items-center justify-content-between mb-3">
            <div class="my-auto mb-2">
                <h3 class="page-title mb-1">Students</h3>
                 <nav>
                       <ol class="breadcrumb mb-0">
                          <li class="breadcrumb-item">
                            <a href="{{url('index')}}">Dashboard</a>
                          </li>
                          <li class="breadcrumb-item">
                            Peoples
                          </li>
                          <li class="breadcrumb-item active" aria-current="page">Students Grid</li>
                    </ol>
                  </nav>
            </div>
            <div class="d-flex my-xl-auto right-content align-items-center flex-wrap">
                  <div class="pe-1 mb-2">
                       <a href="#" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Refresh" data-bs-original-title="Refresh">
                         <i class="ti ti-refresh"></i>
                    </a>
                </div>
                  <div class="pe-1 mb-2">
                    <button type="button" class="btn btn-outline-light bg-white btn-icon me-1" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Print" data-bs-original-title="Print">
                         <i class="ti ti-printer"></i>
                    </button>
                 </div>    
                <div class="dropdown me-2 mb-2">
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
                  <div class="mb-2">
                    <a href="{{url('add-student')}}" class="btn btn-primary d-flex align-items-center"><i class="ti ti-square-rounded-plus me-2"></i>Add Student</a>
                  </div>
            </div>
        </div>
        <!-- /Page Header -->
        
        <!-- Filter -->
        <div class="bg-white p-3 border rounded-1 d-flex align-items-center justify-content-between flex-wrap mb-4 pb-0">
            <h4 class="mb-3">Students  Grid</h4>
            <div class="d-flex align-items-center flex-wrap">		
                <div class="input-icon-start mb-3 me-2 position-relative">
                    <span class="icon-addon">
                        <i class="ti ti-calendar"></i>
                    </span>
                    <input type="text" class="form-control  date-range bookingrange" placeholder="Select" value="Academic Year : 2024 / 2025">
                </div>
                <div class="dropdown mb-3 me-2">
                    <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"  data-bs-toggle="dropdown"  data-bs-auto-close="outside"><i class="ti ti-filter me-2"></i>Filter</a>
                    <div class="dropdown-menu drop-width">
                        <form action="{{url('student-grid')}}">
                            <div class="d-flex align-items-center border-bottom p-3">
                                <h4>Filter</h4>
                            </div>
                            <div class="p-3 pb-0 border-bottom">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Class</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>I</option>
                                                <option>II</option>
                                                <option>III</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Section</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>A</option>
                                                <option>B</option>
                                                <option>C</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>Janet</option>
                                                <option>Joann</option>
                                                <option>Kathleen</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Gender</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>Male</option>
                                                <option>Female</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="select">
                                                <option>Select</option>
                                                <option>Active</option>
                                                <option>Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 d-flex align-items-center justify-content-end">
                                <a href="#" class="btn btn-light me-3">Reset</a>
                                <button type="submit" class="btn btn-primary">Apply</button>
                            </div>
                        </form>									
                    </div>
                </div>
                <div class="d-flex align-items-center bg-white border rounded-2 p-1 mb-3 me-2">
                    <a href="{{url('students')}}" class="btn btn-icon btn-sm me-1 bg-light primary-hover"><i class="ti ti-list-tree"></i></a>
                    <a href="{{url('student-grid')}}" class="active btn btn-icon btn-sm primary-hover"><i class="ti ti-grid-dots"></i></a>
                </div>
                <div class="dropdown mb-3">
                    <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"  data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z </a>
                    <ul class="dropdown-menu p-3">
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                Ascending
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                Descending
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                Recently Viewed
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" class="dropdown-item rounded-1">
                                Recently Added
                            </a>
                        </li>
                    </ul>
                </div>
            </div>	
        </div>
        <!-- /Filter -->

        <div class="row">

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892434</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-01.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h5 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Janet  Daniel</a></h5>
                                    <p>III, A</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35013</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Female</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">10 Jan 2015</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892433</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-02.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Joann Michael</a></h6>
                                    <p>IV, B</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35012</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Male</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">19 Aug 2014</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892432</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-03.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Kathleen Dison</a></h6>
                                    <p>III, A</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35011</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Female</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">5 Dec 2017</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class=" col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892431</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-danger d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-04.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Lisa Gourley </a></h6>
                                    <p>II, B</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35010</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Female</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">13 May 2017</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892430</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-05.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Ralph Claudia</a></h6>
                                    <p>II, B</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35009</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Male</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">20 Jun 20215</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892429</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Ralph Claudia</a></h6>
                                    <p>II, B</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35008</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Male</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">20 Jun 20215</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892428</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-06.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Julie Scott</a></h6>
                                    <p>V, A</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35007</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Female</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">18 Jan 2023</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892427</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-09.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Susan Boswell</a></h6>
                                    <p>VIII, B</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35006</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Female</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">26 May 2020</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892426</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-08.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Richard Mayes</a></h6>
                                    <p>V, A</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35005</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Male</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">6 Oct 2022</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892425</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-12.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Richard Mayes</a></h6>
                                    <p>VII, B</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35004</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Male</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">6 Oct 2022</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->
            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892424</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-11.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Veronica Randle</a></h6>
                                    <p>IX, A</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35003</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Female</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">27 Dec 2009</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <!-- Student Grid -->
            <div class="col-xxl-3 col-xl-4 col-md-6 d-flex">
                <div class="card flex-fill">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <a href="{{url('student-details')}}" class="link-primary">AD9892423</a>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-soft-success d-inline-flex align-items-center me-1"><i class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                            <div class="dropdown">
                                <a href="#" class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="ti ti-dots-vertical fs-14"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-right p-3">
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-details')}}"><i class="ti ti-menu me-2"></i>View Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('edit-student')}}"><i class="ti ti-edit-circle me-2"></i>Edit</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="{{url('student-promotion')}}"><i class="ti ti-arrow-ramp-right-2 me-2"></i>Promote Student</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-1" href="#" data-bs-toggle="modal" data-bs-target="#delete-modal"><i class="ti ti-trash-x me-2"></i>Delete</a>
                                    </li>
                                </ul>	
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="bg-light-300 rounded-2 p-3 mb-3">
                            <div class="d-flex align-items-center">
                                <a href="{{url('student-details')}}" class="avatar avatar-lg flex-shrink-0"><img src="{{URL::asset('build/img/students/student-10.jpg')}}" class="img-fluid rounded-circle" alt="img"></a> 
                                <div class="ms-2">
                                    <h6 class="mb-0" class="text-dark text-truncate"><a href="{{url('student-details')}}">Thomas Hunt</a></h6>
                                    <p>X, A</p>
                                </div>
                            </div>	
                        </div>
                        <div class="d-flex align-items-center justify-content-between gx-2">
                            <div>
                                <p class="mb-0">Roll No</p>
                                <p class="text-dark">35002</p>
                            </div>
                            <div>
                                <p class="mb-0">Gender</p>
                                <p class="text-dark">Male</p>
                            </div>
                            <div>
                                <p class="mb-0">Joined On</p>
                                <p class="text-dark">11 Aug 2008</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-brand-hipchat"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i class="ti ti-phone"></i></a>
                            <a href="#" class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3"><i class="ti ti-mail"></i></a>
                        </div>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#add_fees_collect" class="btn btn-light btn-sm fw-semibold">Add Fees</a>
                    </div>
                </div>
            </div>
            <!-- /Student Grid -->

            <div class="col-md-12 text-center">
                <a href="#" class="btn btn-primary"><i class="ti ti-loader-3 me-2"></i>Load More</a>
            </div>

        </div>

    </div>
</div>
<!-- /Page Wrapper -->
@component('components.modal-popup')
    @endcomponent
@endsection
