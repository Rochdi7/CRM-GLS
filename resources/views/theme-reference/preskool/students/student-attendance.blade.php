<?php $page = 'student-attendance'; ?>
@extends('layout.mainlayout')
@section('content')
 <!-- Page Wrapper -->
 <div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
		@slot('title')
		Student Attendance
		@endslot
		@slot('item1')
		Dashboard
		@endslot
		@slot('item2')
		Report
		@endslot
		@slot('item3')
		Student Attendance
		@endslot
	   @endcomponent
       

        <!-- Student List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Student Attendance List</h4>
                <div class="d-flex align-items-center flex-wrap">
                    <div class="input-icon-start mb-3 me-2 position-relative">
                        <span class="icon-addon">
                            <i class="ti ti-calendar"></i>
                        </span>
                        <input type="text" class="form-control date-range bookingrange" placeholder="Select"
                        value="Academic Year : 2024 / 2025">
                    </div>
                    <div class="dropdown mb-3 me-2">
                        <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                                class="ti ti-filter me-2"></i>Filter</a>
                        <div class="dropdown-menu drop-width">
                            <form action="{{url('student-attendance')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Admission No</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>AD9892424</option>
                                                    <option>AD9892425</option>
                                                    <option>AD9892426</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Roll No</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>35004</option>
                                                    <option>35005</option>
                                                    <option>35006</option>
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
                                            <div class="mb-0">
                                                <label class="form-label">Class</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>XI</option>
                                                    <option>VII</option>
                                                    <option>VIII</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-0">
                                                <label class="form-label">Section</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>A</option>
                                                    <option>B</option>
                                                    <option>C</option>
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
                    <div class="dropdown mb-3">
                        <a href="javascript:void(0);" class="btn btn-outline-light bg-white dropdown-toggle"
                            data-bs-toggle="dropdown"><i class="ti ti-sort-ascending-2 me-2"></i>Sort by A-Z
                        </a>
                        <ul class="dropdown-menu p-3">
                            <li>
                                <a href="javascript:void(0);" class="dropdown-item rounded-1 active">
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
                                <th class="no-sort">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>Admission No</th>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Class </th>
                                <th>Section</th>
                                <th>Attendance</th>
                                <th style="min-width: 200px;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
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
                                <td>III</td>
                                <td>A</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student1" checked>
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student1">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student1">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student1">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student1">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control" placeholder="Enter Name" />
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
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
                                <td>IV</td>
                                <td>B</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student2" checked>
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student2">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student2">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student2">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student2">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" placeholder="Enter Name"></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
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
                                <td>II</td>
                                <td>A</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student3" checked>
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student3">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student3">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student3">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student3">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" placeholder="Enter Name"></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
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
                                <td>B</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student4">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student4" checked>
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student4">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student4">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student4">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" placeholder="Enter Name"></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
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
                                <td>II</td>
                                <td>B</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student5">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student5">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student5">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student5" checked>
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student5">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" placeholder="Enter Name"></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">AD9892429</a></td>
                                <td>35008</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-06.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="javascript:void(0);">Ralph</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>II</td>
                                <td>B</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student6">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student6">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student6">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student6">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student6" checked>
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" placeholder="Enter Name"></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
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
                                <td>V</td>
                                <td>A</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student7">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student7" checked>
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student7">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student7">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student7">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" placeholder="Enter Name"></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
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
                                <td>VI</td>
                                <td>A</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student8" checked>
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student8">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student8">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student8">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student8">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" placeholder="Enter Name"></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
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
                                <td>VIII</td>
                                <td>B</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student9">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student9">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student9" checked>
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student9">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student9">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" placeholder="Enter Name"></td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
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
                                <td>VII</td>
                                <td>B</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="student10">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student10">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student10" checked>
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student10">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="student10">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control" placeholder="Enter Name"></td>
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
