<?php $page = 'roles-permission'; ?>
@extends('layout.mainlayout')
@section('content')
<!-- Page Wrapper -->
<div class="page-wrapper">
    <div class="content">
        @component('components.breadcrumb')
        @slot('title')
        Roles & Permissions
        @endslot
        @slot('item1')
        Dashboard
        @endslot
        @slot('item2')
        User Management
        @endslot
        @slot('item3')
        Roles & Permissions
        @endslot
       @endcomponent
       
       
        <!-- Filter Section -->
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap pb-0">
                <h4 class="mb-3">Roles & Permissions List</h4>
                <div class="d-flex align-items-center flex-wrap">
                    <div class="input-icon-start mb-3 me-2 position-relative">
                        <span class="icon-addon">
                            <i class="ti ti-calendar"></i>
                        </span>
                        <input type="text" class="form-control  date-range bookingrange" placeholder="Select"
                            value="Academic Year : 2024 / 2025">
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
                <!-- Role Permission List -->
                <div class="custom-datatable-filter table-responsive">
                    <table class="table datatable">
                        <thead class="thead-light">
                            <tr>
                                <th class="no-sort">
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th>Role Name</th>
                                <th>Created On</th>
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
                                <td>Admin</td>
                                <td>27 Mar 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>Student</td>
                                <td>20 Mar 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>Parent</td>
                                <td>16 Mar 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>Guardian</td>
                                <td>26 Feb 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>Librarian</td>
                                <td>15 Feb 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>Accountant</td>
                                <td>13 Feb 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>Driver</td>
                                <td>28 Jan 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>Coach</td>
                                <td>21 Jan 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>Warden</td>
                                <td>10 Jan 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-md">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>Therapist</td>
                                <td>24 Dec 2024</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2" data-bs-toggle="modal" data-bs-target="#edit_role"><i
                                                class="ti ti-edit-circle text-primary"></i></a>
                                        <a href="{{url('permission')}}"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle  p-0 me-2"><i
                                                class="ti ti-shield text-skyblue"></i></a>
                                        <a href="#"
                                            class="btn btn-outline-light bg-white btn-icon d-flex align-items-center justify-content-center rounded-circle p-0 me-3" data-bs-toggle="modal" data-bs-target="#delete-modal"><i
                                                class="ti ti-trash-x text-danger"></i></a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- /Role Permission List -->
            </div>
        </div>
        <!-- /Filter Section -->
    </div>
</div>
<!-- /Page Wrapper -->

@component('components.modal-popup')        
@endcomponent
@endsection