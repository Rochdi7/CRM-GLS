<?php $page = 'users'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Users
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
        User Management
        @endslot
        @slot('item3')
        Users
        @endslot
       @endcomponent
    
 <!-- Filter Section -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Users List</h4>
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
                            <form action="{{url('users')}}">
                                <div class="d-flex align-items-center border-bottom p-3">
                                    <h4>Filter</h4>
                                </div>
                                <div class="p-3 border-bottom">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="mb-0">
                                                <label class="form-label">Users</label>
                                                <select class="select">
                                                    <option>Select</option>
                                                    <option>Students</option>
                                                    <option>Teacher</option>
                                                    <option>Parent</option>
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
            <!-- User List -->
            <div class="card-body p-0 py-3">
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
                                <th>Class</th>
                                <th>Section</th>
                                <th>Date of Joined</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461893</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-01.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Janet</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>III</td>
                                <td>A</td>
                                <td>25 Mar 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                              
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461892</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-02.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Joann</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>IV</td>
                                <td>B</td>
                                <td>18 Mar 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                               
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461891</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-03.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Kathleen</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>II</td>
                                <td>A</td>
                                <td>14 Mar 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                               
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461890</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-04.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Gifford</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>I</td>
                                <td>B</td>
                                <td>27 Feb 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                               
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461889</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-05.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Lisa</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>II</td>
                                <td>B</td>
                                <td>13 Feb 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                               
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461888</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-06.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Ralph</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>III</td>
                                <td>B</td>
                                <td>11 Feb 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                               
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461887</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-07.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Julie</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>V</td>
                                <td>A</td>
                                <td>24 Jan 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                                
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461886</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-08.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Ryan</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>VI</td>
                                <td>A</td>
                                <td>19 Jan 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                               
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461885</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-09.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Susan</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>VIII</td>
                                <td>B</td>
                                <td>08 Jan 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                               
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td><a href="#" class="link-primary">U461884</a></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#" class="avatar avatar-md"><img
                                                src="{{URL::asset('build/img/students/student-12.jpg')}}"
                                                class="img-fluid rounded-circle" alt="img"></a>
                                        <div class="ms-2">
                                            <p class="text-dark mb-0"><a href="#">Richard</a>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td>VII</td>
                                <td>B</td>
                                <td>22 Dec 2024</td>
                                <td>
                                    <span class="badge badge-soft-success d-inline-flex align-items-center"><i
                                            class="ti ti-circle-filled fs-5 me-1"></i>Active</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="dropdown">
                                            <a href="#"
                                                class="btn btn-white btn-icon btn-sm d-flex align-items-center justify-content-center rounded-circle p-0"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="ti ti-dots-vertical fs-14"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-right p-3">

                                               
                                                <li>
                                                    <a class="dropdown-item rounded-1" href="#"
                                                        data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                            class="ti ti-trash-x me-2"></i>Delete</a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- /User List -->

        </div>
        <!-- /Filter Section -->




        <div class="row align-items-center">
            <div class="col-md-12">
                <div class="datatable-paginate mt-4"></div>
            </div>
        </div>

    </div>
</div>
<!-- /Page Wrapper -->

	


@endsection