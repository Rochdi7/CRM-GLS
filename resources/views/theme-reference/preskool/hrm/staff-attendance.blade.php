<?php $page = 'staff-attendance'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Staff Attendance
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
        Report
        @endslot
        @slot('item3')
        Staff Attendance
        @endslot
       @endcomponent
        

        <!-- Student List -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Staff Attendance List</h4>
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
                            <form action="{{url('staff-attendance')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-3">
                                                <label class="form-label">ID</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>T849127</option>
                                                    <option>T849125</option>
                                                    <option>T849126</option>
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
                                                <label class="form-label">Department</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Admin</option>
                                                    <option>Management</option>
                                                    <option>Finance</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-0">
                                                <label class="form-label">Designation</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Technical Head</option>
                                                    <option>Receptionist</option>
                                                    <option>Accountant</option>
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
                                <th class="no-sort">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Department </th>
                                <th>Designation</th>
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
                                <td><a href="#" class="link-primary">ST738198</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/profiles/avatar-27.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="javascript:void(0);">Kevin</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>Admin</td>
                                <td>Technical Head</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff1" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff1">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff1">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff1">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff1">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">ST738197</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/teachers/teacher-05.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="javascript:void(0);">Willie</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>Management</td>
                                <td>Receptionist</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff2" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff2">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff2">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff2">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff2">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">ST738196</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/teachers/teacher-02.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="javascript:void(0);">Daniel</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>Management</td>
                                <td>Admin</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff3" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff3">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff3">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff3">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff3">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">ST738195</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/teachers/teacher-01.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="javascript:void(0);">Teresa</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>Management</td>
                                <td>Admin</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff4" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff4">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff4">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff4">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff4">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">ST738194</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/teachers/teacher-08.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="javascript:void(0);">Johnson</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>Finance</td>
                                <td>Accountant</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff5" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff5">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff5">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff5">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff5">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">ST738193</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/teachers/teacher-03.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="javascript:void(0);">Hellana</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>Management</td>
                                <td>HR Manager</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff6" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff6">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff6">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff6">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff6">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">ST738192</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/members/members-01.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="javascript:void(0);">James</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>Library</td>
                                <td>Librarian</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff7" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff7">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff7">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff7">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff7">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">ST738191</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/teachers/teacher-07.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a
                                                    href="javascript:void(0);">Jacquelin</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>Transport</td>
                                <td>Driver</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff8" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff8">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff8">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff8">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff8">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">ST738190</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/teachers/teacher-10.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="javascript:void(0);">Edward</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>Finance</td>
                                <td>Accounts Manager</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff9" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff9">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff9">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff9">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff9">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">ST738189</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="javascript:void(0);" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/teachers/teacher-09.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a
                                                    href="javascript:void(0);">Elizabeth</a></p>
                                        </div>
                                    </div>
                                </td>
                                <td>Management</td>
                                <td>Receptionist</td>
                                <td>
                                    <div class="d-flex align-items-center check-radio-group flex-nowrap">
                                        <label class="custom-radio">
                                            <input type="radio" name="staff10" checked="">
                                            <span class="checkmark"></span>
                                            Present
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff10">
                                            <span class="checkmark"></span>
                                            Late
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff10">
                                            <span class="checkmark"></span>
                                            Absent
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff10">
                                            <span class="checkmark"></span>
                                            Holiday
                                        </label>
                                        <label class="custom-radio">
                                            <input type="radio" name="staff10">
                                            <span class="checkmark"></span>
                                            Halfday
                                        </label>
                                    </div>
                                </td>
                                <td>

                                    <input type="text" class="form-control" placeholder="Enter Name">

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
