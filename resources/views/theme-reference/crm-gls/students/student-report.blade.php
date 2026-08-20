<?php $page = 'student-report'; ?>
@extends('layout.mainlayout')
@section('content')
	<!-- Page Wrapper -->
    <div class="page-wrapper">
        <div class="content">
            @component('components.breadcrumb')
            @slot('title')
            Student Report
            @endslot
            @slot('item1')
            Dashboard
            @endslot
            @slot('item2')
            Report
            @endslot
            @slot('item3')
            Student Report
            @endslot
           @endcomponent
            
         <!-- Student List -->
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                    <h4 class="mb-3">Student Report List</h4>
                    <div class="d-flex align-items-center flex-wrap">
                        <div class="input-icon-start mb-3 me-2 position-relative">
                            <span class="icon-addon">
                                <i class="ti ti-calendar"></i>
                            </span>
                            <input type="text" class="form-control  date-range bookingrange" placeholder="Select"
                                value="Academic Year : 2024 / 2025">
                        </div>
                        <div class="dropdown mb-3 me-2">
                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                                data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                    class="ti ti-filter me-2"></i>Filter</a>
                            <div class="dropdown-menu drop-width">
                                <form action="{{url('student-report')}}">
                                    <div class="d-flex align-items-center border-bottom p-3">
                                        <h4>Filter</h4>
                                    </div>
                                    <div class="p-3 border-bottom">
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
                                            <div class="col-md-12">
                                                <div class="mb-0">
                                                    <label class="form-label">Date of Join</label>
                                                    <input type="date" class="form-control">
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
                        <div class="dropdown mb-3">
                            <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                                data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z
                            </a>
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
                <div class="card-body p-0 py-3">
                    <!-- Student List -->
                    <div class="custom-datatable-filter table-responsive">
                        <table class="table datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Admission No</th>
                                    <th>Roll No</th>
                                    <th>Name</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Gender</th>
                                    <th>Parent</th>
                                    <th>Date of Join</th>
                                    <th>DOB</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892434</a></td>
                                    <td>35013</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-01.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Janet</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Female</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Thomas</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>25 Mar 2024</td>
                                    <td>10 Jan 2015</td>
                                    <td>
                                        <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892433</a></td>
                                    <td>35012</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-02.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Joann</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Male</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Marquita</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>18 Mar 2024</td>
                                    <td>19 Aug 2014</td>
                                    <td>
                                        <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892432</a></td>
                                    <td>35011</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-03.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Kathleen</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Female</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/parents/parent-03.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Johnson</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>14 Mar 2024</td>
                                    <td>05 Dec 2017</td>
                                    <td>
                                        <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892431</a></td>
                                    <td>35010</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-04.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Gifford</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Male</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/parents/parent-04.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Claudia</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>27 Feb 2024</td>
                                    <td>22 Mar 2018</td>
                                    <td>
                                        <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892430</a></td>
                                    <td>35009</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-05.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Lisa</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Female</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/parents/parent-05.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Arthur</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>13 Feb 2024</td>
                                    <td>13 May 2017</td>
                                    <td>
                                        <span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892429</a></td>
                                    <td>35008</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-06.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Lisa</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Male</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/parents/parent-06.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Colleen</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>11 Feb 2024</td>
                                    <td>20 Jun 2015</td>
                                    <td>
                                        <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892428</a></td>
                                    <td>35007</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-07.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Julie</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Female</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/parents/parent-07.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Robert </a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>24 Jan 2024</td>
                                    <td>18 Sep 2013</td>
                                    <td>
                                        <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892427</a></td>
                                    <td>35006</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-08.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Ryan</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Male</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/parents/parent-08.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Jessie</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>19 Jan 2024</td>
                                    <td>26 Nov 2012</td>
                                    <td>
                                        <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892426</a></td>
                                    <td>35005</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-09.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Susan</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Female</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/parents/parent-09.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Michael</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>08 Jan 2024</td>
                                    <td>26 May 2010</td>
                                    <td>
                                        <span class="badge badge-soft-danger d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Inactive</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><a href="#" class="link-primary">AD9892425</a></td>
                                    <td>35004</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/students/student-10.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Richard</a>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>I</td>
                                    <td>A</td>
                                    <td>Male</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                    src="{{URL::asset('build/img/parents/parent-10.jpg')}}"
                                                    class="img-fluid rounded-circle" alt="img"></a>
                                            <div class="ms-2">
                                                <p class="text-dark mb-0"><a href="javascript:void(0);">Mary</a></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>22 Dec 2024</td>
                                    <td>06 Oct 2011</td>
                                    <td>
                                        <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                                class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- /Student List -->
                </div>
            </div>
            <!-- /Student List -->

        </div>
    </div>
    <!-- /Page Wrapper -->

@endsection